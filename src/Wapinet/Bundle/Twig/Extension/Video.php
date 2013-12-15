<?php

namespace Wapinet\Bundle\Twig\Extension;

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
     * @return string
     */
    public function convert3gpToMp4 ($path)
    {
        $mp4File = $path . '.mp4';

        if (false === file_exists(\AppKernel::getWebDir() . $mp4File)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            $video = $ffmpeg->open(\AppKernel::getWebDir() . $path);
            $video->save(new X264('libvo_aacenc'), \AppKernel::getWebDir() . $mp4File);
        }

        return $mp4File;
    }

    /**
     * @param string $path
     * @return string
     */
    public function getScreenshot($path)
    {
        $screenshot = $path . '.jpg';

        if (false === file_exists(\AppKernel::getWebDir() . $screenshot)) {
            $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
            $second = $this->container->getParameter('wapinet_video_screenshot_second');

            $frame = $ffmpeg->open(\AppKernel::getWebDir() . $path)->frame(TimeCode::fromSeconds($second));
            $frame->save(\AppKernel::getWebDir() . $screenshot);
        }

        return $screenshot;
    }


    /**
     * @param File $file
     *
     * @return Stream
     */
    public function getInfo (File $file)
    {
        $ffprobe = $this->container->get('dubture_ffmpeg.ffprobe');

        return $ffprobe->streams($file->getPathname())->videos()->first();
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
