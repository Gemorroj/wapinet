<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    protected function buildContainer()
    {
        $container = parent::buildContainer();

        $this->createDir(self::getTmpDir());
        $this->createDir($this->getCacheWeatherDir());

        return $container;
    }

    /**
     * @param string $dir
     *
     * @throws RuntimeException
     * @return bool
     */
    protected function createDir($dir)
    {
        if (!is_dir($dir)) {
            if (false === @mkdir($dir, 0777, true)) {
                throw new \RuntimeException(sprintf("Unable to create the tmp directory (%s)\n", $dir));
            }
        } elseif (!is_writable($dir)) {
            throw new \RuntimeException(sprintf("Unable to write in the tmp directory (%s)\n", $dir));
        }

        return true;
    }


    /**
     * @return string
     */
    public function getCacheWeatherDir()
    {
        return $this->getCacheDir() . '/weather';
    }


    /**
     * Tmp directory
     *
     * @return string
     */
    public static function getTmpDir()
    {
        return __DIR__.'/tmp';
    }

    /**
     * Web directory
     *
     * @return string
     */
    public static function getWebDir()
    {
        return __DIR__.'/../web';
    }


    /**
     * Returns the kernel parameters.
     *
     * @return array An array of kernel parameters
     */
    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.web_dir'] = $this->getWebDir();
        $parameters['kernel.tmp_dir'] = $this->getTmpDir();

        return $parameters;
    }


    /**
     * @return \Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles()
    {
        $bundles = array(
            new Sonata\AdminBundle\SonataAdminBundle(),
            new Sonata\DoctrineORMAdminBundle\SonataDoctrineORMAdminBundle(),
            new Sonata\BlockBundle\SonataBlockBundle(),
            new Knp\Bundle\MenuBundle\KnpMenuBundle(),
            new Sonata\jQueryBundle\SonatajQueryBundle(),
            new Bmatzner\JQueryBundle\BmatznerJQueryBundle(),
            new Bmatzner\JQueryMobileBundle\BmatznerJQueryMobileBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new FOS\RestBundle\FOSRestBundle(),
            new FOS\CommentBundle\FOSCommentBundle(),
            new JMS\SerializerBundle\JMSSerializerBundle($this),
            new FOS\MessageBundle\FOSMessageBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Vich\UploaderBundle\VichUploaderBundle(),

            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Symfony\Bundle\AsseticBundle\AsseticBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new Wapinet\UserBundle\WapinetUserBundle(),
            new Wapinet\Bundle\WapinetBundle(),
            new Wapinet\MessageBundle\WapinetMessageBundle(),
            new Wapinet\CommentBundle\WapinetCommentBundle(),
            new Wapinet\NewsBundle\WapinetNewsBundle(),
            new Wapinet\UploaderBundle\WapinetUploaderBundle(),
        );

        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
            $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
        }

        return $bundles;
    }


    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config/config_'.$this->getEnvironment().'.yml');
    }
}
