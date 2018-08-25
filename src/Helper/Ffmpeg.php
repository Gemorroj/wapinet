<?php

namespace App\Helper;

use FFMpeg\FFMpeg as FFmpegOriginal;
use FFMpeg\FFProbe;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
        return FFProbe::create([
            'ffmpeg.binaries' => $this->container->getParameter('wapinet_ffmpeg_path'),
            'ffprobe.binaries' => $this->container->getParameter('wapinet_ffprobe_path'),
            'ffmpeg.threads' => $this->container->getParameter('wapinet_threads_count'),
        ]);
    }

    private function createFfmpeg()
    {
        return FFmpegOriginal::create([
            'ffmpeg.binaries' => $this->container->getParameter('wapinet_ffmpeg_path'),
            'ffprobe.binaries' => $this->container->getParameter('wapinet_ffprobe_path'),
            'ffmpeg.threads' => $this->container->getParameter('wapinet_threads_count'),
        ]);
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
