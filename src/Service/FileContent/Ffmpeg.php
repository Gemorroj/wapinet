<?php

namespace App\Service\FileContent;

use FFMpeg\FFMpeg as FFmpegOriginal;
use FFMpeg\FFProbe;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

final class Ffmpeg
{
    private ?FFmpegOriginal $ffmpeg = null;
    private ?FFProbe $ffprobe = null;

    public function __construct(
        #[Autowire('%env(FFMPEG_PATH)%')]
        private readonly string $ffmpegPath,
        #[Autowire('%env(FFPROBE_PATH)%')]
        private readonly string $ffprobePath,
    ) {
    }

    public function getFfmpeg(): FFmpegOriginal
    {
        $this->ffmpeg = $this->ffmpeg ?: FFmpegOriginal::create([
            'ffmpeg.binaries' => $this->ffmpegPath,
            'ffprobe.binaries' => $this->ffprobePath,
            'ffmpeg.threads' => 0, // 0 -autodetect
        ]);

        return $this->ffmpeg;
    }

    public function getFfprobe(): FFProbe
    {
        $this->ffprobe = $this->ffprobe ?: FFProbe::create([
            'ffprobe.binaries' => $this->ffprobePath,
        ]);

        return $this->ffprobe;
    }

    /**
     * Saves a single video frame as an image.
     *
     * php-ffmpeg writes the frame with `-f image2` and no `-update 1`, which is
     * rejected by ffmpeg 9 ("does not contain an image sequence pattern"), so
     * the frame is extracted with a direct call instead.
     */
    public function screenshot(string $input, string $output, int $second): void
    {
        $arguments = [$this->ffmpegPath, '-y'];

        // Seeking to 0 on some files produces no frame at all, so the first frame is taken as is.
        if ($second > 0) {
            $arguments[] = '-ss';
            $arguments[] = (string) $second;
        }

        $arguments = [
            ...$arguments,
            '-i',
            $input,
            '-frames:v',
            '1',
            '-update',
            '1',
            '-q:v',
            '3',
            // Some videos have a non-standard YUV range, which breaks the JPEG encoder.
            '-pix_fmt',
            'yuvj420p',
            $output,
        ];

        new Process($arguments)->mustRun();
    }
}
