<?php

namespace Wapinet\Bundle\Twig\Extension;

use FFMpeg\Filters\Audio\AudioResamplableFilter;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Media\Video as FFmpegVideo;
use FFMpeg\Format\Video\WebM;
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
            new \Twig_SimpleFilter('wapinet_video_3gp_to_mp4', array($this, 'convert3gpToMp4')),
            new \Twig_SimpleFilter('wapinet_video_wmv_to_mp4', array($this, 'convertWmvToMp4')),
            new \Twig_SimpleFilter('wapinet_video_avi_to_webm', array($this, 'convertAviToWebm')),
        );
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function convert3gpToMp4 ($path)
    {
        return $this->convertToMp4($path);
    }


    /**
     * @param string $path
     * @return string|null
     */
    public function convertWmvToMp4 ($path)
    {
        return $this->convertToMp4($path);
    }


    /**
     * @param string $path
     * @return string|null
     */
    private function convertToMp4 ($path)
    {
        $mp4File = $path . '.mp4';

        if (false === \file_exists($this->getWebDir() . $mp4File)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);

                $format = new X264();
                $this->setOptions($format, $media);

                $media->save($format, $this->getWebDir() . $mp4File);

                if (false === \file_exists($this->getWebDir() . $mp4File)) {
                    throw new \RuntimeException('Не удалось создать MP4 файл');
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return $mp4File;
    }

    /**
     * @param DefaultVideo $format
     * @param FFmpegVideo $media
     * @return Video
     */
    protected function setOptions(DefaultVideo $format, FFmpegVideo $media)
    {
        $streams = $media->getStreams();

        $videoStream = $streams->videos()->first();
        $audioStream = $streams->audios()->first();

        if (null !== $videoStream) {
            // https://trac.ffmpeg.org/wiki/Encode/MPEG-4
            // bitrate = file size / duration

            $filesize = @\filesize($media->getPathfile());
            $filesize *= 3.3; // увеличиваем предположительный размер mp4 файла по сравнению с оригиналом
            $filesize /= 1024; // переводим байты в килобайты
            $duration = $videoStream->has('duration') ? $videoStream->get('duration') : 0;
            if ($filesize && $duration) {
                $bitrate = $filesize / $duration;

                if (null !== $audioStream) {
                    $audioBitrate = $audioStream->has('bit_rate') ? $audioStream->get('bit_rate') : 8000;
                    $audioBitrate /= 1000;
                    $bitrate -= $audioBitrate;
                }
                $bitrate = \floor($bitrate);

                if ($bitrate < $format->getKiloBitrate()) {
                    $format->setKiloBitrate($bitrate);
                }
            }
        }

        if (null !== $audioStream && 'libmp3lame' === $format->getAudioCodec()) {
            if ($audioStream->get('sample_rate') < 32000) {
                $media->addFilter(new AudioResamplableFilter(32000));
            }
        }

        return $this;
    }


    /**
     * @param string $path
     * @return string|null
     */
    public function convertAviToWebm ($path)
    {
        $webmFile = $path . '.webm';

        if (false === \file_exists($this->getWebDir() . $webmFile)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);
                $media->save(new WebM(), $this->getWebDir() . $webmFile);
                if (false === \file_exists($this->getWebDir() . $webmFile)) {
                    throw new \RuntimeException('Не удалось создать WEBM файл');
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return $webmFile;
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function getScreenshot($path)
    {
        $screenshot = $path . '.jpg';

        if (false === \file_exists($this->getWebDir() . $screenshot)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');

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
        $duration = $media->getStreams()->videos()->first()->get('duration');

        if ($duration && $duration < $second) {
            $second = \ceil($duration / 2);
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
