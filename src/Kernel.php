<?php

namespace App;

use App\DependencyInjection\Compiler\FileUrlCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FileUrlCompilerPass());
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log';
    }

    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.public_dir'] = $this->getProjectDir().'/public';
        $parameters['kernel.tmp_dir'] = $this->getProjectDir().'/var/tmp';
        $parameters['kernel.tmp_archiver_dir'] = $this->getProjectDir().'/var/tmp/archiver';
        $parameters['kernel.tmp_file_dir'] = $this->getProjectDir().'/var/tmp/file';

        return $parameters;
    }

    public function registerBundles()
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    protected function buildContainer()
    {
        $container = parent::buildContainer();

        foreach (['tmp', 'tmp/archiver', 'tmp/file'] as $dir) {
            $fullPathDir = $this->getProjectDir().'/var/'.$dir;

            if (!\is_dir($fullPathDir)) {
                if (false === @\mkdir($fullPathDir, 0777, true) && !\is_dir($fullPathDir)) {
                    throw new \RuntimeException(\sprintf("Unable to create the var/%s directory\n", $dir));
                }
            } elseif (!\is_writable($fullPathDir)) {
                throw new \RuntimeException(\sprintf("Unable to write in the var/%s directory\n", $dir));
            }
        }

        return $container;
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        $container->setParameter('container.dumper.inline_class_loader', true);
        $confDir = $this->getProjectDir().'/config';

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->environment.self::CONFIG_EXTS, 'glob');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $confDir = $this->getProjectDir().'/config';

        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }
}
