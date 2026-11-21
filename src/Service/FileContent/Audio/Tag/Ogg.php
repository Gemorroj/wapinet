<?php

declare(strict_types=1);

namespace App\Service\FileContent\Audio\Tag;

/**
 * Rewrites the Vorbis comment packet of an OGG file without external tools.
 * Header pages are rebuilt when the packet length changes; audio pages are
 * kept and only renumbered with a fresh CRC.
 */
final readonly class Ogg
{
    private const string VENDOR = 'wapinet';

    public function clearTags(string $path): void
    {
        $this->writeTags($path, new AudioTagData([], true));
    }

    public function writeTags(string $path, AudioTagData $data): void
    {
        $contents = @\file_get_contents($path);

        if (false === $contents) {
            throw new \RuntimeException('Не удалось открыть OGG-файл.');
        }

        $parsed = $this->parse($contents);
        $stream = $this->targetStream($parsed);

        if (null === $stream) {
            return;
        }

        $comment = $this->commentPacket($data, $stream['oldComment']);
        $headerPagesEnd = $stream['headerPagesEnd'];

        foreach ($parsed['pages'] as $page) {
            if ($page['serial'] !== $stream['serial']) {
                throw new \RuntimeException('OGG с несколькими потоками не поддерживается.');
            }
        }

        $page0 = $parsed['pages'][0];
        $page0Raw = \substr($contents, $page0['offset'], 27 + \strlen($page0['segments']) + $page0['dataLength']);
        $header = $this->buildHeaderPages([$comment, $stream['setup']], $stream['serial'], $page0['sequence'] + 1);
        $sequence = $header['nextSequence'];
        $audio = '';

        foreach (\array_slice($parsed['pages'], $headerPagesEnd + 1) as $page) {
            $audio .= $this->page(
                $page['headerType'],
                $page['granule'],
                $page['serial'],
                $sequence++,
                $page['segments'],
                \substr($contents, $page['dataOffset'], $page['dataLength']),
            );
        }

        $this->write($path, $page0Raw.$header['data'].$audio);
    }

    /**
     * @return array{
     *     pages: list<array{offset: int, headerType: int, granule: string, serial: int, sequence: int, segments: string, dataOffset: int, dataLength: int}>,
     *     packets: array<int, list<array{data: string, pages: list<int>}>>
     * }
     */
    private function parse(string $data): array
    {
        $limit = \strlen($data);
        $offset = 0;
        $pages = [];
        $buffers = [];
        $packets = [];

        while ($offset + 27 <= $limit && 'OggS' === \substr($data, $offset, 4)) {
            $serialData = \unpack('Vserial', \substr($data, $offset + 14, 4));
            $sequenceData = \unpack('Vsequence', \substr($data, $offset + 18, 4));
            $serial = \is_array($serialData) ? (int) $serialData['serial'] : 0;
            $sequence = \is_array($sequenceData) ? (int) $sequenceData['sequence'] : 0;
            $segmentCount = \ord($data[$offset + 26]);

            if ($offset + 27 + $segmentCount > $limit) {
                throw new \RuntimeException('Не удалось прочитать OGG-файл: повреждён файл.');
            }

            $segments = \substr($data, $offset + 27, $segmentCount);
            $dataOffset = $offset + 27 + $segmentCount;
            $dataLength = 0;

            for ($index = 0; $index < $segmentCount; ++$index) {
                $dataLength += \ord($segments[$index]);
            }

            if ($dataOffset + $dataLength > $limit) {
                throw new \RuntimeException('Не удалось прочитать OGG-файл: повреждён файл.');
            }

            $pageIndex = \count($pages);
            $pages[] = [
                'offset' => $offset,
                'headerType' => \ord($data[$offset + 5]),
                'granule' => \substr($data, $offset + 6, 8),
                'serial' => $serial,
                'sequence' => $sequence,
                'segments' => $segments,
                'dataOffset' => $dataOffset,
                'dataLength' => $dataLength,
            ];

            $buffer = $buffers[$serial] ?? ['data' => '', 'pages' => []];
            $position = $dataOffset;

            for ($index = 0; $index < $segmentCount; ++$index) {
                $length = \ord($segments[$index]);
                $buffer['data'] .= \substr($data, $position, $length);
                $buffer['pages'][] = $pageIndex;
                $position += $length;

                if ($length < 255) {
                    $packets[$serial][] = $buffer;
                    $buffer = ['data' => '', 'pages' => []];
                }
            }

            $buffers[$serial] = $buffer;
            $offset = $dataOffset + $dataLength;
        }

        return ['pages' => $pages, 'packets' => $packets];
    }

    /**
     * @param array{pages: list<array{offset: int, headerType: int, granule: string, serial: int, sequence: int, segments: string, dataOffset: int, dataLength: int}>, packets: array<int, list<array{data: string, pages: list<int>}>>} $parsed
     *
     * @return array{serial: int, setup: string, oldComment: string, headerPagesEnd: int}|null
     */
    private function targetStream(array $parsed): ?array
    {
        foreach ($parsed['packets'] as $serial => $packets) {
            if (\count($packets) < 3 || !\str_starts_with($packets[0]['data'], "\x01vorbis") || !\str_starts_with($packets[1]['data'], "\x03vorbis")) {
                continue;
            }

            $headerPages = [];

            for ($index = 0; $index < 3; ++$index) {
                foreach ($packets[$index]['pages'] as $pageIndex) {
                    $headerPages[$pageIndex] = true;
                }
            }

            $headerPagesEnd = \max(\array_keys($headerPages));

            if (0 !== $packets[0]['pages'][0]
                || \in_array(0, $packets[1]['pages'], true)
                || \in_array(0, $packets[2]['pages'], true)
                || [] !== \array_diff(\range(0, $headerPagesEnd), \array_keys($headerPages))) {
                throw new \RuntimeException('Не удалось прочитать OGG-файл: нестандартное расположение заголовков.');
            }

            return [
                'serial' => $serial,
                'setup' => $packets[2]['data'],
                'oldComment' => $packets[1]['data'],
                'headerPagesEnd' => $headerPagesEnd,
            ];
        }

        return null;
    }

    private function commentPacket(AudioTagData $data, string $oldPacket): string
    {
        $entries = [];

        foreach ($data->comments as $name => $values) {
            foreach ($values as $value) {
                if ('' !== $value) {
                    $entries[] = \strtoupper($name).'='.$value;
                }
            }
        }

        $oldEntries = $this->parseCommentEntries($oldPacket);

        if ($data->removePicture) {
            $oldEntries = \array_values(\array_filter(
                $oldEntries,
                static fn (array $entry): bool => !\in_array(\strtoupper($entry[0]), ['METADATA_BLOCK_PICTURE', 'COVERART', 'COVERARTMIME'], true),
            ));
        } elseif (null !== $data->pictureData) {
            $oldEntries = \array_values(\array_filter(
                $oldEntries,
                static fn (array $entry): bool => !\in_array(\strtoupper($entry[0]), ['METADATA_BLOCK_PICTURE', 'COVERART', 'COVERARTMIME'], true),
            ));
            $entries[] = 'METADATA_BLOCK_PICTURE='.\base64_encode($this->pictureBlock($data->pictureData, $data->pictureMime));
        }

        $comments = [];

        foreach ($oldEntries as [$name, $value]) {
            if (\in_array(\strtoupper($name), ['METADATA_BLOCK_PICTURE', 'COVERART', 'COVERARTMIME'], true)) {
                $comments[] = $name.'='.$value;
            }
        }

        $comments = \array_merge($comments, $entries);
        $body = \pack('V', \strlen(self::VENDOR)).self::VENDOR.\pack('V', \count($comments));

        foreach ($comments as $comment) {
            $body .= \pack('V', \strlen($comment)).$comment;
        }

        return "\x03vorbis".$body."\x01";
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function parseCommentEntries(string $packet): array
    {
        if (\strlen($packet) < 15 || !\str_starts_with($packet, "\x03vorbis")) {
            throw new \RuntimeException('Не удалось прочитать OGG-файл: повреждён комментарий.');
        }

        $offset = 7;
        $vendor = \unpack('Vlength', \substr($packet, $offset, 4));

        if (!\is_array($vendor)) {
            throw new \RuntimeException('Не удалось прочитать OGG-файл: повреждён комментарий.');
        }

        $offset += 4 + (int) $vendor['length'];
        $count = \unpack('Vcount', \substr($packet, $offset, 4));

        if (!\is_array($count)) {
            throw new \RuntimeException('Не удалось прочитать OGG-файл: повреждён комментарий.');
        }

        $offset += 4;
        $entries = [];

        for ($index = 0; $index < (int) $count['count']; ++$index) {
            $length = \unpack('Vlength', \substr($packet, $offset, 4));

            if (!\is_array($length)) {
                throw new \RuntimeException('Не удалось прочитать OGG-файл: повреждён комментарий.');
            }

            $offset += 4;
            $entry = \substr($packet, $offset, (int) $length['length']);
            $offset += (int) $length['length'];
            $separator = \strpos($entry, '=');

            if (false === $separator) {
                continue;
            }

            $entries[] = [\substr($entry, 0, $separator), \substr($entry, $separator + 1)];
        }

        return $entries;
    }

    private function pictureBlock(string $data, ?string $mime): string
    {
        $mime = 'image/jpg' === $mime ? 'image/jpeg' : (string) $mime;
        $size = @\getimagesizefromstring($data);
        $width = \is_array($size) ? (int) $size[0] : 0;
        $height = \is_array($size) ? (int) $size[1] : 0;
        $depth = \is_array($size) ? (int) ($size['bits'] ?? 0) : 0;

        return \pack('N', 3)
            .\pack('N', \strlen($mime)).$mime
            .\pack('N', 0)
            .\pack('N', $width)
            .\pack('N', $height)
            .\pack('N', $depth)
            .\pack('N', 0)
            .\pack('N', \strlen($data)).$data;
    }

    /**
     * @param list<string> $packets
     *
     * @return array{data: string, nextSequence: int}
     */
    private function buildHeaderPages(array $packets, int $serial, int $sequence): array
    {
        $pages = '';
        $segments = [];
        $payload = '';
        $continued = false;
        $nextSequence = $sequence;

        foreach ($packets as $packet) {
            $length = \strlen($packet);
            $offset = 0;

            while ($length - $offset >= 255) {
                $segments[] = 255;
                $payload .= \substr($packet, $offset, 255);
                $offset += 255;

                if (255 === \count($segments)) {
                    ['page' => $page, 'continued' => $continued, 'sequence' => $nextSequence] = $this->flushHeaderPage(
                        $segments,
                        $payload,
                        $continued,
                        $nextSequence,
                        $serial,
                    );
                    $pages .= $page;
                    $segments = [];
                    $payload = '';
                }
            }

            $remaining = $length - $offset;
            $segments[] = $remaining;
            $payload .= \substr($packet, $offset, $remaining);

            if (255 === \count($segments)) {
                ['page' => $page, 'continued' => $continued, 'sequence' => $nextSequence] = $this->flushHeaderPage(
                    $segments,
                    $payload,
                    $continued,
                    $nextSequence,
                    $serial,
                );
                $pages .= $page;
                $segments = [];
                $payload = '';
            }
        }

        if ([] !== $segments) {
            ['page' => $page, 'continued' => $continued, 'sequence' => $nextSequence] = $this->flushHeaderPage(
                $segments,
                $payload,
                $continued,
                $nextSequence,
                $serial,
            );
            $pages .= $page;
        }

        return ['data' => $pages, 'nextSequence' => $nextSequence];
    }

    /**
     * @param list<int> $segments
     *
     * @return array{page: string, continued: bool, sequence: int}
     */
    private function flushHeaderPage(array $segments, string $payload, bool $continued, int $sequence, int $serial): array
    {
        $page = $this->page(
            $continued ? 0x01 : 0,
            \str_repeat("\x00", 8),
            $serial,
            $sequence,
            \implode('', \array_map(static fn (int $length): string => \chr($length), $segments)),
            $payload,
        );

        return [
            'page' => $page,
            'continued' => 255 === $segments[\count($segments) - 1],
            'sequence' => $sequence + 1,
        ];
    }

    private function page(int $headerType, string $granule, int $serial, int $sequence, string $segments, string $payload): string
    {
        $page = 'OggS'."\x00".\chr($headerType).$granule.\pack('V', $serial).\pack('V', $sequence)
            ."\x00\x00\x00\x00".\chr(\strlen($segments)).$segments.$payload;
        $crc = \pack('V', $this->crc($page));

        for ($index = 0; $index < 4; ++$index) {
            $page[22 + $index] = $crc[$index];
        }

        return $page;
    }

    private function write(string $path, string $data): void
    {
        $temporaryPath = \tempnam(\dirname($path), 'ogg');

        if (false === $temporaryPath || \strlen($data) !== @\file_put_contents($temporaryPath, $data)) {
            if (false !== $temporaryPath) {
                @\unlink($temporaryPath);
            }

            throw new \RuntimeException('Не удалось создать временный файл для OGG.');
        }

        $permissions = @\fileperms($path);

        if (false !== $permissions) {
            @\chmod($temporaryPath, $permissions & 0o777);
        }

        if (!@\rename($temporaryPath, $path)) {
            if (!@\copy($temporaryPath, $path)) {
                @\unlink($temporaryPath);

                throw new \RuntimeException('Не удалось заменить OGG-файл.');
            }

            @\unlink($temporaryPath);
        }
    }

    private function crc(string $data): int
    {
        static $table = null;

        if (null === $table) {
            $table = [];

            for ($index = 0; $index < 256; ++$index) {
                $value = $index << 24;

                for ($bit = 0; $bit < 8; ++$bit) {
                    $value = 0 !== ($value & 0x80000000) ? ($value << 1) ^ 0x04C11DB7 : $value << 1;
                    $value &= 0xFFFFFFFF;
                }

                $table[$index] = $value;
            }
        }

        $crc = 0;

        for ($index = 0, $length = \strlen($data); $index < $length; ++$index) {
            $crc = (($crc << 8) & 0xFFFFFFFF) ^ $table[(($crc >> 24) ^ \ord($data[$index])) & 0xFF];
        }

        return $crc;
    }
}
