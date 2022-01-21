<?php

namespace App\Twig\Extension;

use App\Service\Ffmpeg as FfmpegHelper;
use Exception;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Format\Video\X264;
use FFMpeg\Media\AbstractStreamableMedia;
use FFMpeg\Media\Video as FFmpegVideo;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Video extends AbstractExtension
{
    private FfmpegHelper $ffmpegHelper;
    private LoggerInterface $logger;
    private ParameterBagInterface $parameterBag;

    public function __construct(FfmpegHelper $ffmpegHelper, LoggerInterface $logger, ParameterBagInterface $parameterBag)
    {
        $this->ffmpegHelper = $ffmpegHelper;
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_video_screenshot', [$this, 'getScreenshot']),
            new TwigFilter('wapinet_video_to_mp4', [$this, 'convertToMp4']),
        ];
    }

    public function convertToMp4(string $path): ?string
    {
        $mp4File = $path.'.mp4';

        if (!\is_file($this->getPublicDir().$mp4File)) {
            $ffmpeg = $this->ffmpegHelper->getFfmpeg();
            try {
                $media = $ffmpeg->open($this->getPublicDir().$path);

                $format = new X264('aac');
                $this->setOptions($format, $media);

                $media->save($format, $this->getPublicDir().$mp4File);

                if (!\is_file($this->getPublicDir().$mp4File)) {
                    throw new \RuntimeException('Не удалось создать MP4 файл. '.$path);
                }
            } catch (Exception $e) {
                $this->logger->warning('Ошибка при конвертировании видео в MP4. '.$path, [$e]);

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
            $filesize *= 3.3; // увеличиваем предположительный размер mp4 файла по сравнению с оригиналом
            $filesize /= 1024; // переводим байты в килобайты
            $duration = $videoStream->get('duration', 0);
            if ($filesize && $duration) {
                $videoBitrate = $filesize / $duration;

                /*$audioStream = $streams->audios()->first();
                if (null !== $audioStream) {
                    $audioBitrate = $audioStream->get('bit_rate', 8000);
                    $audioBitrate /= 1000;
                    $videoBitrate -= $audioBitrate;
                }*/
                $videoBitrate = \floor($videoBitrate);

                if ($videoBitrate < $format->getKiloBitrate()) {
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
                    $width = (\floor($width / 2) * 2) - 2;
                }
                if ($isOddHeight) {
                    $height = (\floor($height / 2) * 2) - 2;
                }
                $media->filters()->resize(new Dimension($width, $height), 'inset')->synchronize();
            }
        }

        return $this;
    }

    public function getScreenshot(string $path): ?string
    {
        $screenshot = $path.'.jpg';

        if (!\is_file($this->getPublicDir().$screenshot)) {
            $ffmpeg = $this->ffmpegHelper->getFfmpeg();

            try {
                $media = $ffmpeg->open($this->getPublicDir().$path);
                if ($media instanceof FFmpegVideo) {
                    $second = $this->getScreenshotSecond($media);
                    $frame = $media->frame(TimeCode::fromSeconds($second));
                    $frame->save($this->getPublicDir().$screenshot);
                    if (!\is_file($this->getPublicDir().$screenshot)) {
                        throw new \RuntimeException('Не удалось создать скриншот');
                    }
                } else {
                    $this->logger->notice('Не найден видео поток. '.$path);

                    return null;
                }
            } catch (Exception $e) {
                $this->logger->warning('Ошибка при создании скриншота видео. '.$path, [$e]);

                return null;
            }
        }

        return $screenshot;
    }

    private function getScreenshotSecond(FFmpegVideo $media): int
    {
        $second = $this->parameterBag->get('wapinet_video_screenshot_second');
        $video = $media->getStreams()->videos()->first();

        if ($video && $video->has('duration')) {
            $duration = $video->get('duration');

            if ($duration && $duration < $second) {
                $second = \ceil($duration / 2);
            }
        }

        return $second;
    }

    private function getPublicDir(): string
    {
        return $this->parameterBag->get('kernel.project_dir').'/public';
    }
}
