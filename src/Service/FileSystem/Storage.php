<?php

declare(strict_types=1);

namespace App\Service\FileSystem;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Uploaded files stored under the public directory.
 */
final readonly class Storage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicDir,
    ) {
    }

    /**
     * Absolute path of a file by its public URL path.
     */
    public function absolute(string $url): string
    {
        return $this->publicDir.$url;
    }

    /**
     * The result depends on the filesystem, so concurrent writes invalidate it.
     *
     * @phpstan-impure
     */
    public function has(string $url): bool
    {
        return \is_file($this->absolute($url));
    }
}
