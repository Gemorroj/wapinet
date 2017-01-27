<?php
namespace Wapinet\Bundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use FFMpeg\FFProbe;
use FFMpeg\FFMpeg as FFmpegOriginal;

class Ffmpeg
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var FFmpegOriginal
     */
    private $ffmpeg;
    /**
     * @var FFProbe
     */
    private $ffprobe;


    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }


    private function createFfprobe()
    {
        return FFProbe::create(array(
            'ffmpeg.binaries'  => $this->container->getParameter('wapinet.ffmpeg_path'),
            'ffprobe.binaries' => $this->container->getParameter('wapinet.ffprobe_path'),
            'timeout'          => $this->container->getParameter('wapinet.binary_timeout'),
            'ffmpeg.threads'   => $this->container->getParameter('wapinet.threads_count'),
        ));
    }


    private function createFfmpeg()
    {
        return FFmpegOriginal::create(array(
            'ffmpeg.binaries'  => $this->container->getParameter('wapinet.ffmpeg_path'),
            'ffprobe.binaries' => $this->container->getParameter('wapinet.ffprobe_path'),
            'timeout'          => $this->container->getParameter('wapinet.binary_timeout'),
            'ffmpeg.threads'   => $this->container->getParameter('wapinet.threads_count'),
        ));
    }


    public function getFfmpeg()
    {
        return $this->ffmpeg ?: $this->createFfmpeg();
    }

    public function getFfprobe()
    {
        return $this->ffprobe ?: $this->createFfprobe();
    }
}
