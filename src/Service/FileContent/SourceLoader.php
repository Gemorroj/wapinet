<?php

declare(strict_types=1);

namespace App\Service\FileContent;

use App\Service\FileSystem\RemoteFileDownloader;
use App\Service\FileSystem\RemoteFileException;
use App\Service\Network\OutboundUrlBlockedException;

/**
 * Возвращает исходный код из текста или по ссылке с общей защитой от SSRF.
 */
final readonly class SourceLoader
{
    public function __construct(
        private RemoteFileDownloader $downloader,
    ) {
    }

    public function load(?string $code, ?string $url, int $maxSize): string
    {
        $code = null !== $code && '' !== \trim($code) ? $code : null;
        $url = null !== $url && '' !== \trim($url) ? \trim($url) : null;

        if (null === $code && null === $url) {
            throw new SourceException('Укажите код или ссылку.');
        }

        if (null !== $code && null !== $url) {
            throw new SourceException('Укажите что-то одно: код или ссылку.');
        }

        return null !== $url ? $this->download($url, $maxSize) : $code;
    }

    private function download(string $url, int $maxSize): string
    {
        try {
            $file = $this->downloader->download($url, maxSize: $maxSize);
        } catch (OutboundUrlBlockedException|RemoteFileException $exception) {
            throw new SourceException($exception->getMessage(), 0, $exception);
        }

        try {
            $content = \file_get_contents($file->getPathname());

            if (false === $content) {
                throw new SourceException('Не удалось прочитать файл по ссылке.');
            }

            return $content;
        } finally {
            @\unlink($file->getPathname());
        }
    }
}
