<?php

namespace App\Service\FileContent\Video;

use App\Service\FileContent\Ffmpeg as FfmpegHelper;
use App\Service\FileSystem\Storage;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\AbstractStreamableMedia;
use FFMpeg\Media\Video as FFmpegVideo;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class VideoProcessor
{
    public function __construct(
        private readonly FfmpegHelper $ffmpegHelper,
        private readonly Storage $files,
        private readonly LoggerInterface $logger,
        #[Autowire('%wapinet_video_screenshot_second%')]
        private readonly int $screenshotSecond,
    ) {
    }

    public function convertToMp4(string $url): ?string
    {
        $mp4File = $url.'.mp4';

        if (!$this->files->has($mp4File)) {
            $ffmpeg = $this->ffmpegHelper->getFfmpeg();
            try {
                $media = $ffmpeg->open($this->files->absolute($url));

                $format = new X264('aac');
                $this->setOptions($format, $media);

                $media->save($format, $this->files->absolute($mp4File));

                if (!$this->files->has($mp4File)) {
                    throw new \RuntimeException('Не удалось создать MP4 файл. '.$url);
                }
            } catch (\Exception $e) {
                $this->logger->warning('Ошибка при конвертировании видео в MP4. '.$url, [$e]);

                return null;
            }
        }

        return $mp4File;
    }

    protected function setOptions(DefaultVideo $format, AbstractStreamableMedia $media): self
    {
        $streams = $media->getStreams();

        $videoStream = $streams->videos()->first();

        if (null !== $videoStream) {
            // https://trac.ffmpeg.org/wiki/Encode/MPEG-4
            // bitrate = file size / duration

            $filesize = \filesize($media->getPathfile());
            $filesize *= 3.3; // эвристический коэффициент разницы mp4 файла от оригинала и триффика
            $filesize /= 1024; // переводим размер в килобайты
            $duration = $videoStream->get('duration', 0);
            if ($filesize && $duration) {
                $videoBitrate = $filesize / $duration;

                $videoBitrate = (int) \floor($videoBitrate);

                if ($videoBitrate > 0 && $videoBitrate < $format->getKiloBitrate()) {
                    $format->setKiloBitrate($videoBitrate);
                }
            }

            // https://github.com/PHP-FFMpeg/PHP-FFMpeg/issues/711#issuecomment-609039605
            $dimensions = $videoStream->getDimensions();
            $width = $dimensions->getWidth();
            $height = $dimensions->getHeight();
            $isOddWidth = 0 !== $width % 2;
            $isOddHeight = 0 !== $height % 2;

            if ($isOddWidth || $isOddHeight) {
                if ($isOddWidth) {
                    $width = (int) \max(2, (\floor($width / 2) * 2) - 2);
                }
                if ($isOddHeight) {
                    $height = (int) \max(2, (\floor($height / 2) * 2) - 2);
                }
                $media->filters()->resize(new Dimension($width, $height), 'inset')->synchronize();
            }
        }

        return $this;
    }

    /**
     * Existing screenshot of the video, without generating anything.
     */
    public function find(string $url): ?string
    {
        $screenshot = $url.'.jpg';

        return $this->files->has($screenshot) ? $screenshot : null;
    }

    /**
     * A frame of the video as a picture next to the video file.
     */
    public function getScreenshot(string $url): ?string
    {
        $screenshot = $url.'.jpg';

        if ($this->files->has($screenshot)) {
            return $screenshot;
        }

        $ffmpeg = $this->ffmpegHelper->getFfmpeg();

        try {
            $media = $ffmpeg->open($this->files->absolute($url));

            if (!$media instanceof FFmpegVideo) {
                $this->logger->notice('Не найдена дорожка видео. '.$url);

                return null;
            }

            $this->ffmpegHelper->screenshot($media->getPathfile(), $this->files->absolute($screenshot), $this->getScreenshotSecond($media));

            if (!$this->files->has($screenshot)) {
                throw new \RuntimeException('Не удалось создать скриншот. '.$url);
            }
        } catch (\Exception $e) {
            $this->logger->warning('Ошибка при создании скриншота видео. '.$url, [$e]);

            return null;
        }

        return $screenshot;
    }

    private function getScreenshotSecond(FFmpegVideo $media): int
    {
        $second = $this->screenshotSecond;
        $video = $media->getStreams()->videos()->first();

        if (!$video || !$video->has('duration')) {
            return 0;
        }

        $duration = $video->get('duration');

        if ($duration && $duration < $second) {
            $second = (int) \floor($duration / 2);
        }

        return \max(0, $second);
    }
}
