<?php

declare(strict_types=1);

namespace App\Service\FileSystem;

use App\Entity\File\FileUrl;
use App\Service\Network\OutboundUrlBlockedException;
use App\Service\Network\SafeHttpFetcher;
use Riverline\MultiPartParser\StreamedPart;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Downloads an externally linked file into a temporary FileUrl. Shared by the
 * files API and audio tags tool so limits and redirects behave the same everywhere.
 */
final readonly class RemoteFileDownloader
{
    public function __construct(
        private SafeHttpFetcher $fetcher,
        #[Autowire('%kernel.tmp_dir%')]
        private string $tmpDir,
    ) {
    }

    public function download(string $url, int $timeout = 15, int $maxDuration = 120, ?int $maxSize = null): FileUrl
    {
        $scheme = \strtolower((string) \parse_url($url, \PHP_URL_SCHEME));

        if (!\in_array($scheme, ['http', 'https'], true)) {
            throw new RemoteFileException('Поддерживаются только ссылки HTTP и HTTPS.');
        }

        try {
            $response = $this->fetcher->get($url, timeout: $timeout, maxDuration: $maxDuration);
            $status = $response->getStatusCode();
            $headers = $response->getHeaders();
        } catch (OutboundUrlBlockedException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RemoteFileException('Не удалось скачать файл по ссылке.', 0, $exception);
        }

        if ($status >= 300) {
            throw new RemoteFileException('Не удалось скачать файл по ссылке.');
        }

        $maxSize ??= (int) UploadedFile::getMaxFilesize();
        $contentLength = (int) ($response->getInfo('download_content_length') ?? 0);

        if ($contentLength > $maxSize) {
            throw new RemoteFileException('Файл по ссылке слишком большой.');
        }

        $temporaryFile = $this->temporaryFile();
        $handle = \fopen($temporaryFile, 'w');

        if (false === $handle) {
            @\unlink($temporaryFile);

            throw new \RuntimeException('Unable to create a temporary file.');
        }

        $size = 0;
        $success = false;

        try {
            foreach ($this->fetcher->stream($response) as $content) {
                $size += \strlen($content);

                if ($size > $maxSize) {
                    $response->cancel();

                    throw new RemoteFileException('Файл по ссылке слишком большой.');
                }

                \fwrite($handle, $content);
            }

            $success = true;
        } catch (RemoteFileException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RemoteFileException('Не удалось скачать файл по ссылке.', 0, $exception);
        } finally {
            \fclose($handle);

            if (!$success) {
                @\unlink($temporaryFile);
            }
        }

        if (0 === $size) {
            @\unlink($temporaryFile);

            throw new RemoteFileException('Файл по ссылке пустой.');
        }

        return new FileUrl(
            $temporaryFile,
            $this->originalName($headers, $url),
            $headers['content-type'][0] ?? null,
        );
    }

    private function temporaryFile(): string
    {
        $file = \tempnam($this->tmpDir, 'file_url');

        if (false === $file) {
            throw new \RuntimeException('Unable to create a temporary file.');
        }

        return $file;
    }

    /**
     * @param array<string, list<string>> $headers
     */
    private function originalName(array $headers, string $url): string
    {
        $contentDisposition = $headers['content-disposition'][0] ?? null;

        if (null !== $contentDisposition) {
            $name = StreamedPart::getHeaderOption($contentDisposition, 'filename');

            if (null !== $name && '' !== $name) {
                return $name;
            }
        }

        $path = \parse_url($url, \PHP_URL_PATH);

        if (\is_string($path) && '' !== $path && '/' !== $path) {
            $name = \basename(\rawurldecode($path));

            if ('' !== $name && '.' !== $name && '..' !== $name) {
                return $name;
            }
        }

        return 'index.html';
    }
}
