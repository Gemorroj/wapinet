<?php

namespace Wapinet\Bundle\Twig\Extension;

use FFMpeg\Format\Video\WebM;
use FFMpeg\Format\Video\X264;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFProbe\DataMapping\Stream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

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
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_video_info', array($this, 'getInfo')),
        );
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function convert3gpToMp4 ($path)
    {
        $mp4File = $path . '.mp4';

        if (false === file_exists(\AppKernel::getWebDir() . $mp4File)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            try {
                $media = $ffmpeg->open(\AppKernel::getWebDir() . $path);
                $media->save(new X264('libvo_aacenc'), \AppKernel::getWebDir() . $mp4File);
                if (false === file_exists(\AppKernel::getWebDir() . $mp4File)) {
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

        if (false === file_exists(\AppKernel::getWebDir() . $webmFile)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            try {
                $media = $ffmpeg->open(\AppKernel::getWebDir() . $path);
                $media->save(new WebM(), \AppKernel::getWebDir() . $webmFile);
                if (false === file_exists(\AppKernel::getWebDir() . $webmFile)) {
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

        if (false === file_exists(\AppKernel::getWebDir() . $screenshot)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            $second = $this->container->getParameter('wapinet_video_screenshot_second');

            try {
                $media = $ffmpeg->open(\AppKernel::getWebDir() . $path);
                if ($media instanceof \FFMpeg\Media\Video) {
                    $frame = $media->frame(TimeCode::fromSeconds($second));
                    $frame->save(\AppKernel::getWebDir() . $screenshot);
                    if (false === file_exists(\AppKernel::getWebDir() . $screenshot)) {
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
     * @param File $file
     *
     * @return Stream|null
     */
    public function getInfo (File $file)
    {
        $ffprobe = $this->container->get('dubture_ffmpeg.ffprobe');

        try {
            $info = $ffprobe->streams($file->getPathname())->videos()->first();
        } catch (\Exception $e) {
            $info = null;
        }

        return $info;
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
