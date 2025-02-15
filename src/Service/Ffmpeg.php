<?php

namespace App\Service;

use FFMpeg\FFMpeg as FFmpegOriginal;
use FFMpeg\FFProbe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class Ffmpeg
{
    private ?FFmpegOriginal $ffmpeg = null;
    private ?FFProbe $ffprobe = null;

    public function __construct(private readonly ParameterBagInterface $parameterBag)
    {
    }

    public function getFfmpeg(): FFmpegOriginal
    {
        $this->ffmpeg = $this->ffmpeg ?: FFmpegOriginal::create([
            'ffmpeg.binaries' => $this->parameterBag->get('wapinet_ffmpeg_path'),
            'ffprobe.binaries' => $this->parameterBag->get('wapinet_ffprobe_path'),
            'ffmpeg.threads' => $this->parameterBag->get('wapinet_threads_count'),
        ]);

        return $this->ffmpeg;
    }

    public function getFfprobe(): FFProbe
    {
        $this->ffprobe = $this->ffprobe ?: FFProbe::create([
            'ffmpeg.binaries' => $this->parameterBag->get('wapinet_ffmpeg_path'),
            'ffprobe.binaries' => $this->parameterBag->get('wapinet_ffprobe_path'),
            'ffmpeg.threads' => $this->parameterBag->get('wapinet_threads_count'),
        ]);

        return $this->ffprobe;
    }
}
