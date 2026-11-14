<?php

namespace App\Service\FileContent\Audio;

use App\Service\FileContent\Ffmpeg as FfmpegHelper;
use App\Service\FileContent\Getid3;
use App\Service\FileSystem\Storage;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Media\Audio as FFmpegAudio;
use Psr\Log\LoggerInterface;

class AudioProcessor
{
    private const array PREVIEW_EXTENSIONS = ['jpg', 'png', 'webp', 'gif'];

    public function __construct(
        private readonly FfmpegHelper $ffmpegHelper,
        private readonly Getid3 $getid3,
        private readonly Storage $storage,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function convertToMp3(string $url): ?string
    {
        $mp3File = $url.'.mp3';

        if (!$this->storage->has($mp3File)) {
            $ffmpeg = $this->ffmpegHelper->getFfmpeg();
            try {
                $media = $ffmpeg->open($this->storage->absolute($url));

                $format = new Mp3();
                $this->setOptions($format, $media);

                $media->save($format, $this->storage->absolute($mp3File));

                if (!$this->storage->has($mp3File)) {
                    throw new \RuntimeException('Не удалось создать MP3 файл. '.$url);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Ошибка при конвертировании аудио в MP3. '.$url, [$e]);

                return null;
            }
        }

        return $mp3File;
    }

    private function setOptions(DefaultAudio $format, FFmpegAudio $media): self
    {
        $streams = $media->getStreams();
        $audioStream = $streams->audios()->first();

        if ($audioStream->has('bit_rate')) {
            $kiloBitRate = $audioStream->get('bit_rate') / 1000;
            if ($kiloBitRate < $format->getAudioKiloBitrate()) {
                $format->setAudioKiloBitrate($kiloBitRate);
            }
        }

        return $this;
    }

    /**
     * Existing cover of the audio file, without generating anything.
     */
    public function find(string $url): ?string
    {
        foreach (self::PREVIEW_EXTENSIONS as $extension) {
            $preview = $url.'.'.$extension;

            if ($this->storage->has($preview)) {
                return $preview;
            }
        }

        return null;
    }

    /**
     * Embedded cover art as a picture next to the audio file.
     */
    public function getCover(string $url): ?string
    {
        $preview = $this->find($url);

        if (null !== $preview) {
            return $preview;
        }

        try {
            $picture = $this->getid3->getCover($this->storage->absolute($url));
        } catch (\Throwable $e) {
            $this->logger->warning('Ошибка при извлечении обложки аудио. '.$url, [$e]);

            return null;
        }

        if (null === $picture) {
            return null;
        }

        $extension = match (\strtolower($picture['mime'])) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };

        if (null === $extension) {
            return null;
        }

        $preview = $url.'.'.$extension;

        if (false === \file_put_contents($this->storage->absolute($preview), $picture['data'])) {
            return null;
        }

        return $preview;
    }
}
