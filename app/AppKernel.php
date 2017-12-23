<?php

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new EasyCorp\Bundle\EasyAdminBundle\EasyAdminBundle(),
            new FOS\UserBundle\FOSUserBundle(),
            new FOS\MessageBundle\FOSMessageBundle(),
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new WhiteOctober\PagerfantaBundle\WhiteOctoberPagerfantaBundle(),
            new Vich\UploaderBundle\VichUploaderBundle(),
            new Liip\ImagineBundle\LiipImagineBundle(),
            new Gregwar\CaptchaBundle\GregwarCaptchaBundle(),

            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),

            new WapinetUserBundle\WapinetUserBundle(),
            new WapinetBundle\WapinetBundle(),
            new WapinetMessageBundle\WapinetMessageBundle(),
            new WapinetUploaderBundle\WapinetUploaderBundle(),
        ];

        if (\in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();

            if ('dev' === $this->getEnvironment()) {
                $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
                $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
            }
        }

        return $bundles;
    }


    protected function buildContainer()
    {
        $container = parent::buildContainer();

        $this->createDir($this->getTmpDir());
        $this->createDir($this->getTmpArchiverDir());
        $this->createDir($this->getTmpFileDir());

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
        if (!\is_dir($dir)) {
            if (false === @\mkdir($dir, 0777, true)) {
                throw new \RuntimeException(\sprintf("Unable to create the tmp directory (%s)\n", $dir));
            }
        } elseif (!\is_writable($dir)) {
            throw new \RuntimeException(\sprintf("Unable to write in the tmp directory (%s)\n", $dir));
        }

        return true;
    }

    /**
     * @return string
     */
    public function getTmpArchiverDir()
    {
        return $this->getTmpDir().'/archiver';
    }

    /**
     * @return string
     */
    public function getTmpFileDir()
    {
        return $this->getTmpDir().'/file';
    }


    /**
     * Tmp directory
     *
     * @return string
     */
    public function getTmpDir()
    {
        return \dirname(__DIR__).'/var/tmp';
    }

    /**
     * Web directory
     *
     * @return string
     */
    public function getWebDir()
    {
        return \dirname(__DIR__).'/web';
    }


    /**
     * {@inheritdoc}
     */
    protected function getKernelParameters()
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.web_dir'] = $this->getWebDir();
        $parameters['kernel.tmp_dir'] = $this->getTmpDir();

        return $parameters;
    }


    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->setParameter('container.autowiring.strict_mode', true);
            $container->setParameter('container.dumper.inline_class_loader', true);

            $container->addObjectResource($this);
        });
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
