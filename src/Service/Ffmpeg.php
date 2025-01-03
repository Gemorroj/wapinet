<?php

namespace App\Service;

use FFMpeg\FFMpeg as FFmpegOriginal;
use FFMpeg\FFProbe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Ffmpeg
{
    private ?FFmpegOriginal $ffmpeg = null;
    private ?FFProbe $ffprobe = null;

    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    private function createFfprobe(): FFProbe
    {
        return FFProbe::create([
            'ffmpeg.binaries' => $this->parameterBag->get('wapinet_ffmpeg_path'),
            'ffprobe.binaries' => $this->parameterBag->get('wapinet_ffprobe_path'),
            'ffmpeg.threads' => $this->parameterBag->get('wapinet_threads_count'),
        ]);
    }

    private function createFfmpeg(): FFmpegOriginal
    {
        return FFmpegOriginal::create([
            'ffmpeg.binaries' => $this->parameterBag->get('wapinet_ffmpeg_path'),
            'ffprobe.binaries' => $this->parameterBag->get('wapinet_ffprobe_path'),
            'ffmpeg.threads' => $this->parameterBag->get('wapinet_threads_count'),
        ]);
    }

    public function getFfmpeg(): FFmpegOriginal
    {
        return $this->ffmpeg ?? $this->createFfmpeg();
    }

    public function getFfprobe(): FFProbe
    {
        return $this->ffprobe ?? $this->createFfprobe();
    }
}
