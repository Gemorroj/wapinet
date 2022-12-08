<?php

namespace App\Twig\Extension;

use App\Service\Ffmpeg as FfmpegHelper;
use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Media\Audio as FFmpegAudio;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Audio extends AbstractExtension
{
    public function __construct(
        private FfmpegHelper $ffmpegHelper,
        private LoggerInterface $logger,
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_audio_to_mp3', [$this, 'convertToMp3']),
        ];
    }

    public function convertToMp3(string $path): ?string
    {
        $mp3File = $path.'.mp3';

        if (false === \is_file($this->getPublicDir().$mp3File)) {
            $ffmpeg = $this->ffmpegHelper->getFfmpeg();
            try {
                $media = $ffmpeg->open($this->getPublicDir().$path);

                $format = new Mp3();
                $this->setOptions($format, $media);

                $media->save($format, $this->getPublicDir().$mp3File);

                if (false === \is_file($this->getPublicDir().$mp3File)) {
                    throw new \RuntimeException('Не удалось создать MP3 файл');
                }
            } catch (\Exception $e) {
                $this->logger->warning('Ошибка при конвертировании аудио в MP3.', [$e]);

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

    private function getPublicDir(): string
    {
        return $this->parameterBag->get('kernel.project_dir').'/public';
    }
}
