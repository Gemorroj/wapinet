<?php

namespace App\Service\Api\File;

use App\Dto\Api\Archive\ArchiveEntryResponse;
use App\Entity\File;
use App\Service\Archiver\Archive7z;
use Archive7z\Model\Entry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final readonly class FileArchive
{
    private const int MAX_ENTRIES = 1000;
    private const int MAX_ENTRY_SIZE = 50 * 1024 * 1024;

    public function __construct(
        private Archive7z $archive,
        #[Autowire('%kernel.tmp_file_dir%')]
        private string $tmpDir,
    ) {
    }

    /**
     * @return list<ArchiveEntryResponse>
     */
    public function entries(File $file, ?string $archivePassword): array
    {
        try {
            $entries = $this->archive->getEntries($this->archiveFile($file), self::MAX_ENTRIES, $archivePassword);
        } catch (AccessDeniedHttpException|NotFoundHttpException|UnprocessableEntityHttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw $this->mapException($exception);
        }

        $items = [];

        foreach ($entries as $entry) {
            $path = $entry->getUnixPath();

            if (null === $path) {
                continue;
            }

            $items[] = new ArchiveEntryResponse(
                $path,
                (int) $entry->getSize(),
                $entry->isDirectory(),
                $entry->isEncrypted(),
            );
        }

        return $items;
    }

    public function extract(File $file, string $path, ?string $archivePassword): string
    {
        $path = $this->normalizeEntryPath($path);
        $targetDir = $this->tmpDir.\DIRECTORY_SEPARATOR.'api-archive-'.\bin2hex(\random_bytes(8));

        if (!@\mkdir($targetDir, 0o777, true) && !\is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create a temporary archive directory.');
        }

        $archiveFile = $this->archiveFile($file);

        try {
            $entry = $this->findEntry($archiveFile, $path, $archivePassword);

            if (null === $entry || $entry->isDirectory()) {
                throw new NotFoundHttpException('Файл в архиве не найден.');
            }

            if ((int) $entry->getSize() > self::MAX_ENTRY_SIZE) {
                throw new UnprocessableEntityHttpException('Файл в архиве слишком большой.');
            }

            $entry->extract($targetDir);

            $root = \realpath($targetDir);
            $target = \realpath($targetDir.\DIRECTORY_SEPARATOR.$path);

            if (
                false === $root
                || false === $target
                || !\is_file($target)
                || !\str_starts_with(\str_replace('\\', '/', $target), \str_replace('\\', '/', $root).'/')
            ) {
                throw new NotFoundHttpException('Файл в архиве не найден.');
            }

            if ((int) \filesize($target) > self::MAX_ENTRY_SIZE) {
                throw new UnprocessableEntityHttpException('Файл в архиве слишком большой.');
            }

            $temporaryFile = \tempnam($this->tmpDir, 'archive_');

            if (false === $temporaryFile) {
                throw new \RuntimeException('Unable to prepare the extracted archive entry.');
            }

            // tempnam() creates the file, while rename() does not overwrite it on Windows.
            @\unlink($temporaryFile);

            if (!@\rename($target, $temporaryFile)) {
                throw new \RuntimeException('Unable to prepare the extracted archive entry.');
            }

            return $temporaryFile;
        } catch (AccessDeniedHttpException|NotFoundHttpException|UnprocessableEntityHttpException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw $this->mapException($exception);
        } finally {
            $this->removeDirectory($targetDir);
        }
    }

    private function findEntry(BaseFile $archiveFile, string $path, ?string $archivePassword): ?Entry
    {
        return $this->archive->getEntry($archiveFile, $path, $archivePassword);
    }

    private function archiveFile(File $file): BaseFile
    {
        $archiveFile = $file->getFile();

        if (null === $archiveFile) {
            throw new NotFoundHttpException('Файл отсутствует на диске.');
        }

        return $archiveFile;
    }

    private function normalizeEntryPath(string $path): string
    {
        $path = \str_replace('\\', '/', $path);

        if (
            '' === $path
            || \str_contains($path, "\0")
            || \str_starts_with($path, '/')
            || 1 === \preg_match('#^[a-zA-Z]:#', $path)
            || 1 === \preg_match('#(^|/)\.\.(/|$)#', $path)
        ) {
            throw new AccessDeniedHttpException('Недопустимый путь к файлу в архиве.');
        }

        return $path;
    }

    private function mapException(\Throwable $exception): \Throwable
    {
        $message = \mb_strtolower($exception->getMessage());

        if (\str_contains($message, 'password') || \str_contains($message, 'encrypted')) {
            return new AccessDeniedHttpException('Требуется пароль к архиву или он неверен.', $exception);
        }

        return new UnprocessableEntityHttpException('Не удалось прочитать архив.', $exception);
    }

    private function removeDirectory(string $directory): void
    {
        if (!\is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            $item->isDir() ? @\rmdir($item->getPathname()) : @\unlink($item->getPathname());
        }

        @\rmdir($directory);
    }
}
