<?php

declare(strict_types=1);

namespace App\Service\FileContent\Tags;

use App\Service\FileContent\Backend\Getid3;

/**
 * Reads and rewrites RIFF/WAVE chunks and stores tags in a standard
 * `id3 ` chunk. Existing LIST/INFO tags are replaced with ID3v2.
 */
final readonly class Wav
{
    public function __construct(
        private Getid3 $getid3,
    ) {
    }

    public function clearTags(string $path): void
    {
        $this->rewrite($path, new AudioTagData([], true));
    }

    public function writeTags(string $path, AudioTagData $data): void
    {
        $this->rewrite($path, $data);
    }

    private function rewrite(string $path, AudioTagData $data): void
    {
        $source = @\fopen($path, 'r');

        if (false === $source) {
            throw new \RuntimeException('Не удалось открыть WAV-файл.');
        }

        $temporaryPath = \tempnam(\dirname($path), 'wav');

        if (false === $temporaryPath) {
            \fclose($source);

            throw new \RuntimeException('Не удалось создать временный файл для WAV.');
        }

        $target = @\fopen($temporaryPath, 'w+');

        if (false === $target) {
            \fclose($source);
            @\unlink($temporaryPath);

            throw new \RuntimeException('Не удалось создать временный файл для WAV.');
        }

        $failed = true;

        try {
            $this->skipId3($source);
            $size = $this->readRiffHeader($source);
            $tag = $this->id3Tag($data);
            $remaining = $size - 12;

            $this->writeBytes($target, 'RIFF'."\x00\x00\x00\x00".'WAVE');

            while ($remaining >= 8) {
                $header = $this->readBytes($source, 8);
                $chunkId = \substr($header, 0, 4);
                $chunkSizeData = \unpack('Vsize', \substr($header, 4, 4));

                if (!\is_array($chunkSizeData)) {
                    throw new \RuntimeException('Не удалось прочитать WAV-файл: повреждён файл.');
                }

                $chunkSize = (int) $chunkSizeData['size'];
                $paddedSize = $chunkSize + ($chunkSize % 2);
                $remaining -= 8;

                if ($paddedSize > $remaining) {
                    break;
                }

                if ('id3 ' === $chunkId) {
                    $this->skipBytes($source, $paddedSize);
                    $remaining -= $paddedSize;

                    continue;
                }

                if ('LIST' === $chunkId && $chunkSize >= 4) {
                    $listType = $this->readBytes($source, 4);

                    if ('INFO' === $listType) {
                        $this->skipBytes($source, $chunkSize - 4 + ($chunkSize % 2));
                        $remaining -= $chunkSize + ($chunkSize % 2);

                        continue;
                    }

                    $this->writeBytes($target, $header.$listType);
                    $this->copyBytes($source, $target, $chunkSize - 4);
                } else {
                    $this->writeBytes($target, $header);
                    $this->copyBytes($source, $target, $chunkSize);
                }

                if (0 !== $chunkSize % 2) {
                    $this->writeBytes($target, $this->readBytes($source, 1));
                }

                $remaining -= $paddedSize;
            }

            if ('' !== $tag) {
                $this->writeBytes($target, 'id3 '.\pack('V', \strlen($tag)).$tag.(1 === \strlen($tag) % 2 ? "\x00" : ''));
            }

            $written = \ftell($target);

            if (false === $written || $written < 8 || $written > 0xFFFFFFFF) {
                throw new \RuntimeException('Не удалось записать WAV-файл.');
            }

            if (0 !== \fseek($target, 4)) {
                throw new \RuntimeException('Не удалось записать WAV-файл.');
            }

            $this->writeBytes($target, \pack('V', $written - 8));
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

                throw new \RuntimeException('Не удалось заменить WAV-файл.');
            }

            @\unlink($temporaryPath);
        }
    }

    private function id3Tag(AudioTagData $data): string
    {
        $tagData = [];
        $fields = [
            'TITLE' => 'title',
            'ARTIST' => 'artist',
            'ALBUM' => 'album',
            'ALBUMARTIST' => 'band',
            'DATE' => 'year',
            'TRACKNUMBER' => 'track_number',
            'GENRE' => 'genre',
            'COMMENT' => 'comment',
            'COMPOSER' => 'composer',
            'DISCNUMBER' => 'part_of_a_set',
            'COPYRIGHT' => 'copyright_message',
            'LYRICS' => 'unsynchronised_lyric',
            'BPM' => 'bpm',
            'ISRC' => 'isrc',
            'URL_USER' => 'url_user',
        ];

        foreach ($data->comments as $name => $values) {
            $field = $fields[\strtoupper($name)] ?? null;

            if (null !== $field) {
                $tagData[$field] = $values;
            }
        }

        if (null !== $data->pictureData) {
            $tagData['attached_picture'][0] = [
                'data' => $data->pictureData,
                'picturetypeid' => 3,
                'description' => 'image',
                'mime' => $data->pictureMime,
            ];
        }

        if ([] === $tagData) {
            return '';
        }

        $writer = $this->getid3->getId3Writer();
        $writer->tag_encoding = 'UTF-8';
        $writer->tag_data = $tagData;
        $frames = $writer->FormatDataForID3v2(4);

        if (false === $frames) {
            throw new \RuntimeException('Не удалось записать ID3-теги WAV.');
        }

        $id3 = new \getid3_write_id3v2();
        $id3->majorversion = 4;
        $id3->tag_data = $frames;
        $tag = $id3->GenerateID3v2Tag();

        if (!\is_string($tag)) {
            throw new \RuntimeException('Не удалось записать ID3-теги WAV.');
        }

        return $tag;
    }

    /**
     * @param resource $source
     */
    private function readRiffHeader($source): int
    {
        $header = $this->readBytes($source, 12);

        if ('RIFF' !== \substr($header, 0, 4) || 'WAVE' !== \substr($header, 8, 4)) {
            throw new \RuntimeException('Не удалось прочитать WAV-файл: неверный заголовок.');
        }

        $size = \unpack('Vsize', \substr($header, 4, 4));

        if (!\is_array($size)) {
            throw new \RuntimeException('Не удалось прочитать WAV-файл: повреждён файл.');
        }

        return (int) $size['size'] + 8;
    }

    /**
     * @param resource $source
     */
    private function skipId3($source): void
    {
        $magic = $this->readBytes($source, 3);

        if ('ID3' !== $magic) {
            \fseek($source, 0);

            return;
        }

        $id3Header = $magic.$this->readBytes($source, 7);
        $id3Size = ((\ord($id3Header[6]) & 0x7F) << 21)
            | ((\ord($id3Header[7]) & 0x7F) << 14)
            | ((\ord($id3Header[8]) & 0x7F) << 7)
            | (\ord($id3Header[9]) & 0x7F);

        if (0 !== \fseek($source, $id3Size, \SEEK_CUR)) {
            throw new \RuntimeException('Не удалось прочитать WAV-файл.');
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
                throw new \RuntimeException('Не удалось прочитать WAV-файл: повреждён файл.');
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
            throw new \RuntimeException('Не удалось записать WAV-файл.');
        }
    }

    /**
     * @param resource $source
     * @param resource $target
     */
    private function copyBytes($source, $target, int $length): void
    {
        while ($length > 0) {
            $chunk = \fread($source, \min($length, 65536));

            if (false === $chunk || '' === $chunk) {
                throw new \RuntimeException('Не удалось прочитать WAV-файл: повреждён файл.');
            }

            $this->writeBytes($target, $chunk);
            $length -= \strlen($chunk);
        }
    }

    /**
     * @param resource $source
     */
    private function skipBytes($source, int $length): void
    {
        while ($length > 0) {
            $chunk = \fread($source, \min($length, 65536));

            if (false === $chunk || '' === $chunk) {
                throw new \RuntimeException('Не удалось прочитать WAV-файл: повреждён файл.');
            }

            $length -= \strlen($chunk);
        }
    }
}
