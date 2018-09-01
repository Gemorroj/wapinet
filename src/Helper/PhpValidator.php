<?php

namespace App\Helper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Syntax\Php;

/**
 * PhpValidator хэлпер
 */
class PhpValidator
{
    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @param ParameterBagInterface $parameterBag
     */
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param BaseFile $file
     *
     * @throws \Exception
     *
     * @return array
     */
    public function validateFile(BaseFile $file)
    {
        $cli = $this->parameterBag->get('wapinet_php_path');

        $syntax = new Php();
        $syntax->setCli($cli);

        return $syntax->checkFile($file->getPathname());
    }

    /**
     * @param string $source
     *
     * @throws \Exception
     *
     * @return array
     */
    public function validateFragment($source)
    {
        $cli = $this->parameterBag->get('wapinet_php_path');

        $syntax = new Php();
        $syntax->setCli($cli);
        $syntax->setTempDirectory($this->parameterBag->get('kernel.tmp_dir'));

        return $syntax->check($source);
    }
}
