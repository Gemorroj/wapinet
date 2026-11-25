<?php

declare(strict_types=1);

namespace App\Service\Api\Archiver;

use App\Dto\Api\ArchiveEntryResponse;
use App\Dto\Api\Archiver\ArchiveResponse;
use App\Entity\File\FileUrl;
use App\Exception\PublicHttpException;
use App\Service\Archiver\Archive7z;
use App\Service\Archiver\ArchiveLimits;
use App\Service\Archiver\ArchiverException;
use App\Service\Archiver\ArchiveZip;
use App\Service\ByteSize;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * A temporary archive identified by an unguessable token: an anonymous visitor
 * uploads or extracts files, then downloads the resulting ZIP. The token is
 * the only secret; storage is cleaned by app:tmp-clear together with var/tmp.
 */
final readonly class Archiver
{
    public function __construct(
        private ArchiveZip $zip,
        private Archive7z $sevenZip,
        private ArchiveLimits $limits,
        private Filesystem $filesystem,
        #[Autowire('%kernel.tmp_archiver_dir%')]
        private string $archiverDir,
        #[Autowire('%kernel.tmp_dir%')]
        private string $tmpDir,
    ) {
    }

    /**
     * @param non-empty-list<UploadedFile> $sources
     */
    public function create(array $sources): ArchiveResponse
    {
        $this->assertSources($sources);

        $token = $this->newToken();
        $directory = $this->archiverDir.\DIRECTORY_SEPARATOR.$token;
        $this->filesystem->mkdir($directory);

        try {
            foreach ($sources as $source) {
                $this->store($directory, $source);
            }
        } catch (\Throwable $exception) {
            $this->filesystem->remove($directory);

            throw $exception;
        }

        $this->writeInfo($token, 'zip');

        return $this->response($token);
    }

    public function extract(UploadedFile $source): ArchiveResponse
    {
        $this->assertArchiveFile($source);
        $info = $this->sevenZip->getInfo($source);
        $sourceSize = $info?->getPhysicalSize() ?? $this->size($source);

        $token = $this->newToken();
        $directory = $this->archiverDir.\DIRECTORY_SEPARATOR.$token;
        $this->filesystem->mkdir($directory);

        try {
            // 7-Zip decodes legacy ZIP/TAR names and handles self-extracting
            // archives; PHP's ZipArchive remains the fallback when 7-Zip is
            // unavailable on the host.
            if ($this->sevenZip->isValid($source)) {
                $this->sevenZip->extract($directory, $source);
            } elseif ($this->zip->isValid($source)) {
                $this->zip->extract($directory, $source);
            } else {
                throw new PublicHttpException(422, 'Неподдерживаемый тип архива.');
            }
        } catch (ArchiverException $exception) {
            $this->filesystem->remove($directory);

            throw new PublicHttpException(422, $exception->getMessage(), $exception);
        } catch (\Throwable $exception) {
            $this->filesystem->remove($directory);

            throw $exception;
        } finally {
            // FileUrl is a downloaded temp file; an UploadedFile is removed by PHP itself.
            if ($source instanceof FileUrl) {
                @\unlink($source->getPathname());
            }
        }

        $this->writeInfo(
            $token,
            $info?->getType() ?? 'zip',
            $sourceSize,
        );

        return $this->response($token);
    }

    /**
     * @param non-empty-list<UploadedFile> $sources
     */
    public function add(string $token, array $sources): ArchiveResponse
    {
        $directory = $this->directory($token);
        [$count, $size] = $this->usage($directory);
        $this->assertSources($sources, $count, $size);

        foreach ($sources as $source) {
            $this->store($directory, $source);
        }

        return $this->response($token);
    }

    public function delete(string $token): void
    {
        $directory = $this->directory($token);
        $this->filesystem->remove([$directory, $directory.'.tmp', $this->infoPath($token)]);
    }

    public function response(string $token): ArchiveResponse
    {
        $items = $this->entries($token);
        $size = 0;

        foreach ($items as $item) {
            if (!$item->isDirectory) {
                $size += $item->size;
            }
        }

        $info = $this->readInfo($token);

        return new ArchiveResponse($token, $items, $size, $info['format'], $info['size']);
    }

    /**
     * @return list<ArchiveEntryResponse>
     */
    public function entries(string $token): array
    {
        $directory = $this->directory($token);
        $items = [];

        foreach ($this->zip->getFiles($directory) as $file) {
            /* @var \SplFileObject $file */
            $items[] = new ArchiveEntryResponse(
                $this->relativePath($directory, $file->getPathname()),
                $file->isDir() ? 0 : (int) $file->getSize(),
                $file->isDir(),
                false,
            );
        }

        \usort($items, static fn (ArchiveEntryResponse $a, ArchiveEntryResponse $b): int => \strcmp($a->path, $b->path));

        return $items;
    }

    public function remove(string $token, string $path): ArchiveResponse
    {
        $directory = $this->directory($token);
        $this->filesystem->remove($this->resolve($directory, $path));

        return $this->response($token);
    }

    public function filePath(string $token, string $path): string
    {
        $file = $this->resolve($this->directory($token), $path);

        if (\is_dir($file)) {
            throw new PublicHttpException(404, 'Файл не найден.');
        }

        return $file;
    }

    public function zip(string $token): File
    {
        $directory = $this->directory($token);
        $this->assertFreeSpace();

        return $this->zip->create($directory);
    }

    private function store(string $directory, UploadedFile $source): void
    {
        $name = $this->name($source);
        $target = $directory.\DIRECTORY_SEPARATOR.$name;

        if (\is_dir($target)) {
            throw new PublicHttpException(422, 'Файл с таким именем уже есть в архиве.');
        }

        if (\is_file($target)) {
            @\unlink($target);
        }

        $source->move($directory, $name);
    }

    /**
     * @param list<UploadedFile> $sources
     */
    private function assertSources(array $sources, int $count = 0, int $size = 0): void
    {
        if ([] === $sources) {
            throw new PublicHttpException(422, 'Укажите файл или ссылку.');
        }

        $this->assertFreeSpace();

        foreach ($sources as $source) {
            if (!\is_file($source->getPathname())) {
                throw new PublicHttpException(422, 'Файл не загружен.');
            }

            $sourceSize = $this->size($source);

            if ($sourceSize > $this->limits->maxFileSize) {
                throw new PublicHttpException(422, \sprintf('Файл «%s» больше %s.', $this->name($source), ByteSize::format($this->limits->maxFileSize)));
            }

            ++$count;
            $size += $sourceSize;
        }

        if ($count > $this->limits->maxFiles) {
            throw new PublicHttpException(422, \sprintf('В архиве больше %d файлов.', $this->limits->maxFiles));
        }

        if ($size > $this->limits->maxTotalSize) {
            throw new PublicHttpException(422, \sprintf('Суммарный размер архива больше %s.', ByteSize::format($this->limits->maxTotalSize)));
        }
    }

    private function assertArchiveFile(UploadedFile $source): void
    {
        if (!\is_file($source->getPathname())) {
            throw new PublicHttpException(422, 'Файл не загружен.');
        }

        if ($this->size($source) > $this->limits->maxFileSize) {
            throw new PublicHttpException(422, \sprintf('Архив больше %s.', ByteSize::format($this->limits->maxFileSize)));
        }

        $this->assertFreeSpace();
    }

    private function assertFreeSpace(): void
    {
        $free = @\disk_free_space($this->tmpDir);

        if (false !== $free && $free < $this->limits->minFreeSpace) {
            throw new PublicHttpException(503, 'На сервере недостаточно свободного места.');
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function usage(string $directory): array
    {
        $count = 0;
        $size = 0;

        foreach ($this->zip->getFiles($directory) as $file) {
            if ($file->isDir()) {
                continue;
            }

            ++$count;
            $size += (int) $file->getSize();
        }

        return [$count, $size];
    }

    private function name(UploadedFile $source): string
    {
        $name = \basename(\str_replace('\\', '/', $source->getClientOriginalName()));

        if ('' === $name || '.' === $name || '..' === $name) {
            throw new PublicHttpException(422, 'Недопустимое имя файла.');
        }

        return $name;
    }

    private function size(UploadedFile $source): int
    {
        $size = $source->getSize();

        if (false === $size) {
            $size = \filesize($source->getPathname());
        }

        return false === $size ? 0 : (int) $size;
    }

    private function directory(string $token): string
    {
        if (1 !== \preg_match('/^archive[a-z0-9]{8,32}$/', $token)) {
            throw new PublicHttpException(404, 'Архив не найден.');
        }

        $directory = $this->archiverDir.\DIRECTORY_SEPARATOR.$token;

        if (!\is_dir($directory)) {
            throw new PublicHttpException(404, 'Архив не найден или срок его хранения истёк.');
        }

        return $directory;
    }

    private function resolve(string $directory, string $path): string
    {
        $path = \str_replace('\\', '/', $path);

        if ('' === $path || '/' === $path[0] || \str_contains($path, '../')) {
            throw new PublicHttpException(403, 'Недопустимый путь.');
        }

        $base = \realpath($directory);

        if (false === $base) {
            throw new PublicHttpException(404, 'Архив не найден.');
        }

        $file = \realpath($base.\DIRECTORY_SEPARATOR.$path);

        if (false === $file) {
            throw new PublicHttpException(404, 'Файл не найден.');
        }

        $base = \str_replace('\\', '/', $base);
        $normalized = \str_replace('\\', '/', $file);

        if (!\str_starts_with($normalized, $base.'/')) {
            throw new PublicHttpException(403, 'Недопустимый путь.');
        }

        return $file;
    }

    private function relativePath(string $directory, string $path): string
    {
        $path = \str_replace('\\', '/', $path);

        return \ltrim(\substr($path, \strlen(\str_replace('\\', '/', $directory))), '/');
    }

    /**
     * @return array{format: string|null, size: int|null}
     */
    private function readInfo(string $token): array
    {
        $file = $this->infoPath($token);
        $data = \is_file($file) ? \json_decode((string) @\file_get_contents($file), true) : null;

        return [
            'format' => \is_array($data) && \is_string($data['format'] ?? null) ? $data['format'] : null,
            'size' => \is_array($data) && \is_int($data['size'] ?? null) ? $data['size'] : null,
        ];
    }

    private function writeInfo(string $token, ?string $format, ?int $size = null): void
    {
        @\file_put_contents(
            $this->infoPath($token),
            (string) \json_encode(['format' => $format, 'size' => $size]),
        );
    }

    private function infoPath(string $token): string
    {
        return $this->archiverDir.\DIRECTORY_SEPARATOR.$token.'.info.json';
    }

    private function newToken(): string
    {
        return 'archive'.\bin2hex(\random_bytes(8));
    }
}
