<?php

namespace App\Service\Archiver;

use App\Service\Archiver\ArchiverException;
use Archive7z\Archive7z as Base7zArchive;
use Archive7z\Config\Options;
use Archive7z\Exception\ExceptionInterface;
use Archive7z\Model\Entry;
use Archive7z\Model\Info;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File;

final class Archive7z extends Archive
{
    public function __construct(
        ArchiveLimits $limits,
        #[Autowire('%env(P7Z_PATH)%')]
        private readonly string $binaryPath,
    ) {
        parent::__construct($limits);
    }

    public function create(string $directory): File
    {
        throw new ArchiverException('Создание 7z архивов не поддерживается');
    }

    public function isValid(File $file): bool
    {
        try {
            $archive7z = $this->archive($file);

            if (!$archive7z->isValid()) {
                return false;
            }
        } catch (ExceptionInterface) {
            return false;
        }

        $this->validateArchive($archive7z);

        return true;
    }

    public function extract(string $directory, File $file): void
    {
        $this->archive($file)->extract($directory);
    }

    /**
     * @return list<Entry>
     */
    public function getEntries(File $file, ?int $limit = null, ?string $password = null): array
    {
        return $this->archive($file, $password)->getEntries(null, $limit);
    }

    public function getEntry(File $file, string $path, ?string $password = null): ?Entry
    {
        return $this->archive($file, $password)->getEntry($path);
    }

    public function getInfo(File $file, ?string $password = null): ?Info
    {
        try {
            return $this->archive($file, $password)->getInfo();
        } catch (ExceptionInterface) {
            return null;
        }
    }

    public function extractEntry(File $file, string $entry, string $directory, ?string $password = null): void
    {
        $this->archive($file, $password)->extractEntry($entry, $directory);
    }

    private function archive(File $file, ?string $password = null): Base7zArchive
    {
        $options = null === $password || '' === $password ? null : new Options(password: $password);

        return new Base7zArchive(
            $file->getPathname(),
            $options,
            '' === $this->binaryPath ? null : $this->binaryPath,
        );
    }

    private function validateArchive(Base7zArchive $archive7z): void
    {
        $entries = $archive7z->getEntries();

        $this->validateFileCount(\count($entries));

        $totalSize = 0;

        foreach ($entries as $entry) {
            $path = $entry->getPath();

            if (null !== $path) {
                $this->validateArchivePath($path);
            }

            $size = (int) $entry->getSize();
            $this->validateFileSize($size);
            $totalSize += $size;
        }

        $this->validateTotalSize($totalSize);
    }
}
