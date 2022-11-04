<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Syntax\Php;

class PhpValidator
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @throws \Exception
     */
    public function validateFile(BaseFile $file): array
    {
        $cli = $this->parameterBag->get('wapinet_php_path');

        $syntax = new Php();
        $syntax->setCli($cli);

        return $syntax->checkFile($file->getPathname());
    }

    /**
     * @throws \Exception
     */
    public function validateFragment(string $source): array
    {
        $cli = $this->parameterBag->get('wapinet_php_path');

        $syntax = new Php();
        $syntax->setCli($cli);
        $syntax->setTempDirectory($this->parameterBag->get('kernel.tmp_dir'));

        return $syntax->check($source);
    }
}
