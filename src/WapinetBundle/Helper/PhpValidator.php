<?php
namespace WapinetBundle\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Syntax\Php;

/**
 * PhpValidator хэлпер
 */
class PhpValidator
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param BaseFile $file
     * @return array
     * @throws \Exception
     */
    public function validateFile(BaseFile $file)
    {
        $cli = $this->container->getParameter('wapinet_php_path');

        $syntax = new Php();
        $syntax->setCli($cli);

        return $syntax->checkFile($file->getPathname());
    }


    /**
     * @param string $source
     * @return array
     * @throws \Exception
     */
    public function validateFragment($source)
    {
        $cli = $this->container->getParameter('wapinet_php_path');

        $syntax = new Php();
        $syntax->setCli($cli);
        $syntax->setTempDirectory($this->container->get('kernel')->getTmpDir());

        return $syntax->check($source);
    }
}
