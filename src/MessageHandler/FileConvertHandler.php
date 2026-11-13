<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\File;
use App\Enum\FileConversion;
use App\Message\FileConvertMessage;
use App\Repository\FileRepository;
use App\Service\FileContent\Audio;
use App\Service\FileContent\Video;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Vich\UploaderBundle\Storage\StorageInterface;

#[AsMessageHandler]
readonly class FileConvertHandler
{
    public function __construct(
        private FileRepository $fileRepository,
        private StorageInterface $storage,
        private Audio $audio,
        private Video $video,
    ) {
    }

    public function __invoke(FileConvertMessage $message): void
    {
        $file = $this->fileRepository->find($message->fileId);

        // The file could be deleted or replaced before the worker picked the message up.
        if (!$file instanceof File || $message->format !== $file->getConversion()) {
            return;
        }

        $url = $this->storage->resolveUri($file, 'file');
        if (null === $url || '' === $url) {
            return;
        }

        $converted = match ($message->format) {
            FileConversion::Mp3 => $this->audio->convertToMp3($url),
            FileConversion::Mp4 => $this->video->convertToMp4($url),
        };

        if (null === $converted) {
            throw new \RuntimeException(\sprintf('Не удалось конвертировать файл %d в %s.', $message->fileId, $message->format->value));
        }
    }
}
