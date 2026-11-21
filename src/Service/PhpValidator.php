<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Syntax\Checker\TokenizerChecker;
use Syntax\Php;

final readonly class PhpValidator
{
    private Php $syntax;

    public function __construct(
        #[Autowire('%env(PHP_PATH)%')]
        string $phpPath,
    ) {
        if ('' === $phpPath) {
            throw new \InvalidArgumentException('PHP_PATH must not be empty.');
        }

        // TODO: replace with new CliChecker($phpPath) when FrankenPHP/PHP 8.6 supports "-l".
        $this->syntax = new Php(new TokenizerChecker());
    }

    /**
     * @throws \Exception
     */
    public function validateFile(BaseFile $file): array
    {
        return $this->syntax->checkFile($file->getPathname());
    }

    /**
     * @throws \Exception
     */
    public function validateFragment(string $source): array
    {
        return $this->syntax->check($source);
    }
}
