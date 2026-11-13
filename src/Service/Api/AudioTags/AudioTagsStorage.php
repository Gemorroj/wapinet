<?php

declare(strict_types=1);

namespace App\Service\Api\AudioTags;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class AudioTagsStorage
{
    private const string TOKEN_PATTERN = '/^[a-f0-9]{32}$/';

    public function __construct(
        #[Autowire('%kernel.tmp_audio_tags_dir%')]
        private string $directory,
        private Filesystem $filesystem,
    ) {
    }

    public function store(UploadedFile $file, ?string $originalName = null): string
    {
        $token = \bin2hex(\random_bytes(16));
        $originalName = \basename($originalName ?? $file->getClientOriginalName() ?: 'audio');

        $file->move($this->directory, $token);

        $meta = [
            'originalName' => $originalName,
        ];

        \file_put_contents($this->metaPath($token), \json_encode($meta, \JSON_THROW_ON_ERROR));

        return $token;
    }

    public function path(string $token): string
    {
        $this->assertToken($token);

        $path = $this->directory.\DIRECTORY_SEPARATOR.$token;

        if (!\is_file($path)) {
            throw new AudioTagsNotFoundException('Файл не найден или срок хранения истёк.');
        }

        return $path;
    }

    public function originalName(string $token): string
    {
        $this->assertToken($token);

        $metaPath = $this->metaPath($token);

        if (!\is_file($metaPath)) {
            return 'audio';
        }

        try {
            $meta = \json_decode((string) \file_get_contents($metaPath), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return 'audio';
        }

        return \is_array($meta) && \is_string($meta['originalName'] ?? null) && '' !== $meta['originalName']
            ? \basename($meta['originalName'])
            : 'audio';
    }

    public function remove(string $token): void
    {
        $this->assertToken($token);

        $this->filesystem->remove([
            $this->directory.\DIRECTORY_SEPARATOR.$token,
            $this->metaPath($token),
        ]);
    }

    private function metaPath(string $token): string
    {
        return $this->directory.\DIRECTORY_SEPARATOR.$token.'.json';
    }

    private function assertToken(string $token): void
    {
        if (1 !== \preg_match(self::TOKEN_PATTERN, $token)) {
            throw new AudioTagsNotFoundException('Файл не найден или срок хранения истёк.');
        }
    }
}
