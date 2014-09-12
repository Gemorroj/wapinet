<?php

namespace Wapinet\Bundle\Twig\Extension;

use FFMpeg\Filters\Audio\AudioResamplableFilter;
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
            new \Twig_SimpleFilter('wapinet_video_avi_to_webm', array($this, 'convertAviToWebm')),
        );
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function convert3gpToMp4 ($path)
    {
        $mp4File = $path . '.mp4';

        if (false === file_exists($this->getWebDir() . $mp4File)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);
                $media->addFilter(new AudioResamplableFilter(0));
                // 'libvo_aacenc', 'libfaac', 'libmp3lame'
                $media->save(new X264('libmp3lame'), $this->getWebDir() . $mp4File);
                if (false === file_exists($this->getWebDir() . $mp4File)) {
                    throw new \RuntimeException('Не удалось создать MP4 файл');
                }
            } catch (\Exception $e) {
                return null;
            }
        }

        return $mp4File;
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function convertAviToWebm ($path)
    {
        $webmFile = $path . '.webm';

        if (false === file_exists($this->getWebDir() . $webmFile)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);
                $media->save(new WebM(), $this->getWebDir() . $webmFile);
                if (false === file_exists($this->getWebDir() . $webmFile)) {
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

        if (false === file_exists($this->getWebDir() . $screenshot)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            $second = $this->container->getParameter('wapinet_video_screenshot_second');

            try {
                $media = $ffmpeg->open($this->getWebDir() . $path);
                if ($media instanceof \FFMpeg\Media\Video) {
                    $frame = $media->frame(TimeCode::fromSeconds($second));
                    $frame->save($this->getWebDir() . $screenshot);
                    if (false === file_exists($this->getWebDir() . $screenshot)) {
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
