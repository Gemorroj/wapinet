<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\File;
use App\Entity\File\Meta as FileMetaValue;
use App\Message\FilePrepareMessage;
use App\Repository\FileRepository;
use App\Service\FileContent\MetadataExtractor;
use App\Service\FileContent\PreviewGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Vich\UploaderBundle\Storage\StorageInterface;

#[AsMessageHandler]
readonly class FilePrepareHandler
{
    public function __construct(
        private FileRepository $fileRepository,
        private StorageInterface $uploaderStorage,
        private EntityManagerInterface $entityManager,
        private MetadataExtractor $metadataExtractor,
        private PreviewGenerator $previewGenerator,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(FilePrepareMessage $message): void
    {
        $file = $this->fileRepository->find($message->fileId);

        // The file could be deleted or replaced before the worker picked the message up.
        if (!$file instanceof File) {
            return;
        }

        $url = $this->uploaderStorage->resolveUri($file, 'file');

        if (null === $url || '' === $url) {
            return;
        }

        if (null === $file->getMeta()) {
            try {
                $file->setMeta($this->metadataExtractor->getFileMeta($file));
            } catch (\Throwable $exception) {
                $this->logger->warning('Unable to read file meta. '.$url, ['exception' => $exception]);
                // An empty value marks the attempt, so such files are not retried forever
                // (SVG, TIFF and other formats the metadata reader cannot handle).
                $file->setMeta(new FileMetaValue());
            }

            $this->entityManager->flush();
        }

        $this->previewGenerator->generate($file, $url);
    }
}
