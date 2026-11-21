<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Syntax\Php;

final readonly class PhpValidator
{
    public function __construct(
        #[Autowire('%env(PHP_PATH)%')]
        private string $phpPath,
        #[Autowire('%kernel.tmp_dir%')]
        private string $tmpDir,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function validateFile(BaseFile $file): array
    {
        $syntax = new Php();
        $syntax->setCli($this->phpPath);

        return $syntax->checkFile($file->getPathname());
    }

    /**
     * @throws \Exception
     */
    public function validateFragment(string $source): array
    {
        $syntax = new Php();
        $syntax->setCli($this->phpPath);
        $syntax->setTempDirectory($this->tmpDir);

        return $syntax->check($source);
    }
}
