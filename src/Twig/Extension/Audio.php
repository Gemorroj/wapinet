<?php

namespace App\Twig\Extension;

use FFMpeg\Format\Audio\DefaultAudio;
use FFMpeg\Format\Audio\Mp3;
use FFMpeg\Media\Audio as FFmpegAudio;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Audio extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('wapinet_audio_to_mp3', [$this, 'convertToMp3']),
        ];
    }


    /**
     * @param string $path
     * @return string|null
     */
    public function convertToMp3(string $path) : ?string
    {
        $mp3File = $path . '.mp3';

        if (false === \file_exists($this->getPublicDir() . $mp3File)) {
            $ffmpeg = $this->container->get('ffmpeg')->getFfmpeg();
            try {
                $media = $ffmpeg->open($this->getPublicDir() . $path);

                $format = new Mp3();
                $this->setOptions($format, $media);

                $media->save($format, $this->getPublicDir() . $mp3File);

                if (false === \file_exists($this->getPublicDir() . $mp3File)) {
                    throw new \RuntimeException('Не удалось создать MP3 файл');
                }
            } catch (\Exception $e) {
                $this->container->get('logger')->warning('Ошибка при конвертировании аудио в MP3.', [$e]);
                return null;
            }
        }

        return $mp3File;
    }

    /**
     * @param DefaultAudio $format
     * @param FFmpegAudio $media
     * @return $this
     */
    protected function setOptions(DefaultAudio $format, FFmpegAudio $media) : self
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
     * @return string
     */
    protected function getPublicDir() : string
    {
        return $this->container->get('kernel')->getPublicDir();
    }
}
