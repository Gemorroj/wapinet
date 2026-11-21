<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\FileConvertMessage;
use App\Message\FilePrepareMessage;
use App\Repository\FileRepository;
use App\Service\FileContent\PreviewGenerator;
use App\Service\FileSystem\Storage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Queues preparation and conversion for files uploaded before the background processing existed.
 */
#[AsCommand(name: 'app:files:prepare-pending', description: 'Dispatch preparation and conversion messages for files without derivatives')]
final class FilesPreparePendingCommand extends Command
{
    public function __construct(
        private readonly FileRepository $fileRepository,
        private readonly StorageInterface $uploaderStorage,
        private readonly Storage $storage,
        private readonly PreviewGenerator $previewGenerator,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prepared = 0;
        $converted = 0;

        foreach ($this->fileRepository->createQueryBuilder('f')->getQuery()->toIterable() as $file) {
            $url = $this->uploaderStorage->resolveUri($file, 'file');

            if (null === $url || '' === $url) {
                continue;
            }

            $fileId = (int) $file->getId();
            $needsPreview = $this->previewGenerator->needsGeneration($file, $url);

            if (null === $file->getMeta() || $needsPreview) {
                $this->messageBus->dispatch(new FilePrepareMessage($fileId));
                ++$prepared;
            }

            $conversion = $file->getConversion();

            if (null !== $conversion && !$this->storage->has($url.'.'.$conversion->value)) {
                $this->messageBus->dispatch(new FileConvertMessage($fileId, $conversion));
                ++$converted;
            }
        }

        $output->writeln(\sprintf('Queued %d file(s) for preparation and %d for conversion.', $prepared, $converted));

        return Command::SUCCESS;
    }
}
