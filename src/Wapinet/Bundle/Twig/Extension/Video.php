<?php

namespace Wapinet\Bundle\Twig\Extension;

use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Media\Video as FFmpegVideo;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Video extends \Twig_Extension
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
        return array(
            new \Twig_SimpleFilter('wapinet_video_screenshot', array($this, 'getScreenshot')),
            new \Twig_SimpleFilter('wapinet_video_to_mp4', array($this, 'convertToMp4')),
        );
    }


    /**
     * @param string $path
     * @return string|null
     */
    public function convertToMp4 ($path)
    {
        $mp4File = $path . '.mp4';

        if (false === \file_exists($this->getWebDir() . $mp4File)) {
            $ffmpeg = $this->container->get('ffmpeg')->getFfmpeg();
            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);

                $format = new X264('aac');
                $this->setOptions($format, $media);

                $media->save($format, $this->getWebDir() . $mp4File);

                if (false === \file_exists($this->getWebDir() . $mp4File)) {
                    throw new \RuntimeException('Не удалось создать MP4 файл');
                }
            } catch (\Exception $e) {
                $this->container->get('logger')->warning('Ошибка при конвертировании видео в MP4.', array($e));
                return null;
            }
        }

        return $mp4File;
    }

    /**
     * @param DefaultVideo $format
     * @param FFmpegVideo $media
     * @return $this
     */
    protected function setOptions(DefaultVideo $format, FFmpegVideo $media)
    {
        $streams = $media->getStreams();

        $videoStream = $streams->videos()->first();

        if (null !== $videoStream) {
            // https://trac.ffmpeg.org/wiki/Encode/MPEG-4
            // bitrate = file size / duration

            $filesize = @\filesize($media->getPathfile());
            $filesize *= 3.3; // увеличиваем предположительный размер mp4 файла по сравнению с оригиналом
            $filesize /= 1024; // переводим байты в килобайты
            $duration = $videoStream->has('duration') ? $videoStream->get('duration') : 0;
            if ($filesize && $duration) {
                $bitrate = $filesize / $duration;

                /*$audioStream = $streams->audios()->first();
                if (null !== $audioStream) {
                    $audioBitrate = $audioStream->has('bit_rate') ? $audioStream->get('bit_rate') : 8000;
                    $audioBitrate /= 1000;
                    $bitrate -= $audioBitrate;
                }*/
                $bitrate = \floor($bitrate);

                if ($bitrate < $format->getKiloBitrate()) {
                    $format->setKiloBitrate($bitrate);
                }
            }
        }

        return $this;
    }


    /**
     * @param string $path
     * @return string|null
     */
    public function getScreenshot($path)
    {
        $screenshot = $path . '.jpg';

        if (false === \file_exists($this->getWebDir() . $screenshot)) {
            $ffmpeg = $this->container->get('ffmpeg')->getFfmpeg();

            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);
                if ($media instanceof FFmpegVideo) {
                    $second = $this->getScreenshotSecond($media);
                    $frame = $media->frame(TimeCode::fromSeconds($second));
                    $frame->save($this->getWebDir() . $screenshot);
                    if (false === \file_exists($this->getWebDir() . $screenshot)) {
                        throw new \RuntimeException('Не удалось создать скриншот');
                    }
                } else {
                    throw new \RuntimeException('Не найден видео поток');
                }
            } catch (\Exception $e) {
                $this->container->get('logger')->warning('Ошибка при создании скриншота видео.', array($e));
                return null;
            }
        }

        return $screenshot;
    }


    /**
     * @param FFmpegVideo $media
     * @return int
     */
    protected function getScreenshotSecond(FFmpegVideo $media)
    {
        $second = $this->container->getParameter('wapinet_video_screenshot_second');
        $video = $media->getStreams()->videos()->first();

        if ($video && $video->has('duration')) {
            $duration = $video->get('duration');

            if ($duration && $duration < $second) {
                $second = \ceil($duration / 2);
            }
        }

        return (int)$second;
    }


    /**
     * @return string
     */
    protected function getWebDir()
    {
        return $this->container->get('kernel')->getWebDir();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_video';
    }
}
