<?php

namespace App\Tests\Service\Api;

use App\Entity\File;
use App\Enum\FileConversion;
use App\Message\FileConvertMessage;
use App\MessageHandler\FileConvertHandler;
use App\Repository\FileRepository;
use App\Service\FileContent\Audio\AudioProcessor;
use App\Service\FileContent\Video\VideoProcessor;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Storage\StorageInterface;

class FileConvertHandlerTest extends TestCase
{
    public function testConvertsFileInBackground(): void
    {
        $file = new File()->setMimeType('video/x-flv');

        $video = $this->createMock(VideoProcessor::class);
        $video->expects(self::once())->method('convertToMp4')->with('/static/file/video.flv')->willReturn('/static/file/video.flv.mp4');

        $this->handler($file, $this->createStub(AudioProcessor::class), $video)(new FileConvertMessage(1, FileConversion::Mp4));
    }

    public function testConvertsAudio(): void
    {
        $file = new File()->setMimeType('audio/amr');

        $audio = $this->createMock(AudioProcessor::class);
        $audio->expects(self::once())->method('convertToMp3')->with('/static/file/sound.amr')->willReturn('/static/file/sound.amr.mp3');

        $this->handler($file, $audio, $this->createStub(VideoProcessor::class), '/static/file/sound.amr')(new FileConvertMessage(1, FileConversion::Mp3));
    }

    public function testSkipsDeletedFile(): void
    {
        $audio = $this->createMock(AudioProcessor::class);
        $audio->expects(self::never())->method('convertToMp3');
        $video = $this->createMock(VideoProcessor::class);
        $video->expects(self::never())->method('convertToMp4');

        $this->handler(null, $audio, $video)(new FileConvertMessage(1, FileConversion::Mp4));
    }

    public function testSkipsOutdatedFormat(): void
    {
        // The file was replaced with a plain MP4 before the worker started.
        $file = new File()->setMimeType('video/mp4');

        $audio = $this->createMock(AudioProcessor::class);
        $audio->expects(self::never())->method('convertToMp3');
        $video = $this->createMock(VideoProcessor::class);
        $video->expects(self::never())->method('convertToMp4');

        $this->handler($file, $audio, $video)(new FileConvertMessage(1, FileConversion::Mp4));
    }

    public function testFailsWhenConversionReturnsNothing(): void
    {
        $file = new File()->setMimeType('video/x-flv');

        $video = $this->createStub(VideoProcessor::class);
        $video->method('convertToMp4')->willReturn(null);

        $this->expectException(\RuntimeException::class);

        $this->handler($file, $this->createStub(AudioProcessor::class), $video)(new FileConvertMessage(1, FileConversion::Mp4));
    }

    private function handler(?File $file, AudioProcessor $audio, VideoProcessor $video, string $url = '/static/file/video.flv'): FileConvertHandler
    {
        $repository = $this->createStub(FileRepository::class);
        $repository->method('find')->willReturn($file);

        $storage = $this->createStub(StorageInterface::class);
        $storage->method('resolveUri')->willReturn($url);

        return new FileConvertHandler($repository, $storage, $audio, $video);
    }
}
