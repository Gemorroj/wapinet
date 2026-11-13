<?php

namespace App\Tests\MessageHandler;

use App\Entity\File;
use App\Entity\File\Meta as FileMetaValue;
use App\Message\FilePrepareMessage;
use App\MessageHandler\FilePrepareHandler;
use App\Repository\FileRepository;
use App\Service\FileContent\Apk;
use App\Service\FileContent\Backend\Ffmpeg;
use App\Service\FileContent\Backend\Midi;
use App\Service\FileContent\MetadataExtractor;
use App\Service\FileContent\PreviewGenerator;
use App\Service\FileContent\Torrent;
use Doctrine\ORM\EntityManagerInterface;
use Imagine\Image\AbstractImagine;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Vich\UploaderBundle\Storage\StorageInterface;

class FilePrepareHandlerTest extends TestCase
{
    public function testPreparesPreview(): void
    {
        $file = new File()->setMimeType('video/x-msvideo')->setMeta(new FileMetaValue());
        $previewGenerator = $this->createMock(PreviewGenerator::class);
        $previewGenerator->expects(self::once())->method('generate')->with($file, '/static/file/video.avi');

        $this->handler($file, $previewGenerator)(new FilePrepareMessage(1));
    }

    public function testSkipsDeletedFile(): void
    {
        $previewGenerator = $this->createMock(PreviewGenerator::class);
        $previewGenerator->expects(self::never())->method('generate');

        $this->handler(null, $previewGenerator)(new FilePrepareMessage(1));
    }

    private function handler(?File $file, PreviewGenerator $previewGenerator): FilePrepareHandler
    {
        $repository = $this->createStub(FileRepository::class);
        $repository->method('find')->willReturn($file);

        $storage = $this->createStub(StorageInterface::class);
        $storage->method('resolveUri')->willReturn('/static/file/video.avi');

        return new FilePrepareHandler(
            $repository,
            $storage,
            $this->createStub(EntityManagerInterface::class),
            new MetadataExtractor(
                new Ffmpeg('/usr/bin/ffmpeg', '/usr/bin/ffprobe', 3),
                new Apk(),
                new Torrent(),
                $this->createStub(AbstractImagine::class),
                new Midi(),
            ),
            $previewGenerator,
            new NullLogger(),
        );
    }
}
