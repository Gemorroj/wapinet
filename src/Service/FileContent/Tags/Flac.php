<?php

declare(strict_types=1);

namespace App\Service\FileContent\Tags;

/**
 * Reads and rewrites FLAC metadata blocks without external tools.
 */
final readonly class Flac
{
    private const string VENDOR = 'wapinet';

    public function clearTags(string $path): void
    {
        $this->rewrite($path, [], true, null, null);
    }

    public function writeTags(string $path, AudioTagData $data): void
    {
        $this->rewrite($path, $data->comments, $data->removePicture, $data->pictureData, $data->pictureMime);
    }

    /**
     * @param array<string, list<string>> $comments
     */
    private function rewrite(string $path, array $comments, bool $removePicture, ?string $pictureData, ?string $pictureMime): void
    {
        $source = @\fopen($path, 'r');

        if (false === $source) {
            throw new \RuntimeException('Не удалось открыть FLAC-файл.');
        }

        $temporaryPath = \tempnam(\dirname($path), 'flac');

        if (false === $temporaryPath) {
            \fclose($source);

            throw new \RuntimeException('Не удалось создать временный файл для FLAC.');
        }

        $target = @\fopen($temporaryPath, 'w+');

        if (false === $target) {
            \fclose($source);
            @\unlink($temporaryPath);

            throw new \RuntimeException('Не удалось создать временный файл для FLAC.');
        }

        $failed = true;

        try {
            $this->skipId3($source);
            $blocks = $this->readBlocks($source);
            $commentBlock = [] === $comments ? null : $this->commentBlock($comments);
            $pictureBlock = null === $pictureData ? null : $this->pictureBlock($pictureData, $pictureMime);
            $kept = [];
            $commentInserted = false;

            foreach ($blocks as $block) {
                if (4 === $block['type']) {
                    if (null !== $commentBlock && !$commentInserted) {
                        $kept[] = $commentBlock;
                        $commentInserted = true;
                    }

                    continue;
                }

                if (6 === $block['type'] && ($removePicture || null !== $pictureBlock)) {
                    continue;
                }

                $kept[] = $block;
            }

            if (null !== $commentBlock && !$commentInserted) {
                \array_splice($kept, 1, 0, [$commentBlock]);
            }

            if (null !== $pictureBlock) {
                $commentIndex = null;

                foreach ($kept as $index => $block) {
                    if (4 === $block['type']) {
                        $commentIndex = $index;
                        break;
                    }
                }

                \array_splice($kept, null === $commentIndex ? 1 : $commentIndex + 1, 0, [$pictureBlock]);
            }

            if ([] === $kept) {
                throw new \RuntimeException('Не удалось записать теги FLAC: в файле нет метаданных.');
            }

            $this->writeBytes($target, 'fLaC');

            foreach ($kept as $index => $block) {
                $isLast = $index === \array_key_last($kept);
                $this->writeBytes($target, $this->blockHeader($block['type'], \strlen($block['data']), $isLast).$block['data']);
            }

            if (false === \stream_copy_to_stream($source, $target)) {
                throw new \RuntimeException('Не удалось записать теги FLAC.');
            }

            $failed = false;
        } finally {
            \fclose($source);
            \fclose($target);

            if ($failed) {
                @\unlink($temporaryPath);
            }
        }

        $permissions = @\fileperms($path);

        if (false !== $permissions) {
            @\chmod($temporaryPath, $permissions & 0o777);
        }

        if (!@\rename($temporaryPath, $path)) {
            if (!@\copy($temporaryPath, $path)) {
                @\unlink($temporaryPath);

                throw new \RuntimeException('Не удалось заменить FLAC-файл.');
            }

            @\unlink($temporaryPath);
        }
    }

    /**
     * @param resource $source
     *
     * @return list<array{type: int, data: string}>
     */
    private function readBlocks($source): array
    {
        $blocks = [];
        $header = $this->readBytes($source, 4);

        while (true) {
            $type = \ord($header[0]) & 0x7F;
            $length = (\ord($header[1]) << 16) | (\ord($header[2]) << 8) | \ord($header[3]);
            $blocks[] = ['type' => $type, 'data' => $this->readBytes($source, $length)];

            if (0 !== (\ord($header[0]) & 0x80)) {
                return $blocks;
            }

            $header = $this->readBytes($source, 4);
        }
    }

    /**
     * @param array<string, list<string>> $comments
     */
    private function commentBlock(array $comments): array
    {
        $data = \pack('V', \strlen(self::VENDOR)).self::VENDOR.\pack('V', \count($comments));

        foreach ($comments as $name => $values) {
            foreach ($values as $value) {
                $entry = $name.'='.$value;
                $data .= \pack('V', \strlen($entry)).$entry;
            }
        }

        return ['type' => 4, 'data' => $data];
    }

    private function pictureBlock(string $data, ?string $mime): array
    {
        $mime = 'image/jpg' === $mime ? 'image/jpeg' : (string) $mime;
        $size = @\getimagesizefromstring($data);
        $width = \is_array($size) ? (int) $size[0] : 0;
        $height = \is_array($size) ? (int) $size[1] : 0;
        $depth = \is_array($size) ? (int) ($size['bits'] ?? 0) : 0;
        $body = \pack('N', 3)
            .\pack('N', \strlen($mime)).$mime
            .\pack('N', 0)
            .\pack('N', $width)
            .\pack('N', $height)
            .\pack('N', $depth)
            .\pack('N', 0)
            .\pack('N', \strlen($data)).$data;

        return ['type' => 6, 'data' => $body];
    }

    private function blockHeader(int $type, int $length, bool $last): string
    {
        return \chr(($last ? 0x80 : 0) | $type)
            .\chr(($length >> 16) & 0xFF)
            .\chr(($length >> 8) & 0xFF)
            .\chr($length & 0xFF);
    }

    /**
     * @param resource $source
     */
    private function skipId3($source): void
    {
        $magic = $this->readBytes($source, 3);

        if ('ID3' === $magic) {
            $id3Header = $magic.$this->readBytes($source, 7);
            $id3Size = ((\ord($id3Header[6]) & 0x7F) << 21)
                | ((\ord($id3Header[7]) & 0x7F) << 14)
                | ((\ord($id3Header[8]) & 0x7F) << 7)
                | (\ord($id3Header[9]) & 0x7F);

            if (0 !== \fseek($source, $id3Size, \SEEK_CUR)) {
                throw new \RuntimeException('Не удалось прочитать FLAC-файл.');
            }

            $magic = $this->readBytes($source, 4);
        } else {
            $magic .= $this->readBytes($source, 1);
        }

        if ('fLaC' !== $magic) {
            throw new \RuntimeException('Не удалось прочитать FLAC-файл: неверный заголовок.');
        }
    }

    /**
     * @param resource $handle
     */
    private function readBytes($handle, int $length): string
    {
        $data = '';

        while (\strlen($data) < $length) {
            $chunk = \fread($handle, $length - \strlen($data));

            if (false === $chunk || '' === $chunk) {
                throw new \RuntimeException('Не удалось прочитать FLAC-файл: повреждён файл.');
            }

            $data .= $chunk;
        }

        return $data;
    }

    /**
     * @param resource $handle
     */
    private function writeBytes($handle, string $data): void
    {
        if (\strlen($data) !== \fwrite($handle, $data)) {
            throw new \RuntimeException('Не удалось записать FLAC-файл.');
        }
    }
}
