<?php

namespace App;

use App\DependencyInjection\Compiler\FileUrlCompilerPass;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use function dirname;
use function is_dir;
use function is_writable;
use function mkdir;
use function sprintf;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    public function registerBundles(): iterable
    {
        $contents = require $this->getProjectDir().'/config/bundles.php';
        foreach ($contents as $class => $envs) {
            if ($envs[$this->environment] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
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

        $routes->import($confDir.'/{routes}/'.$this->environment.'/**/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FileUrlCompilerPass());
    }

    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();

        foreach (['tmp', 'tmp/archiver', 'tmp/file'] as $dir) {
            $fullPathDir = $this->getProjectDir().'/var/'.$dir;

            if (!is_dir($fullPathDir)) {
                if (false === @mkdir($fullPathDir, 0777, true) && !is_dir($fullPathDir)) {
                    throw new RuntimeException(sprintf("Unable to create the var/%s directory\n", $dir));
                }
            } elseif (!is_writable($fullPathDir)) {
                throw new RuntimeException(sprintf("Unable to write in the var/%s directory\n", $dir));
            }
        }

        return $container;
    }

    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();
        $parameters['kernel.tmp_dir'] = $this->getProjectDir().'/var/tmp';
        $parameters['kernel.tmp_archiver_dir'] = $this->getProjectDir().'/var/tmp/archiver';
        $parameters['kernel.tmp_file_dir'] = $this->getProjectDir().'/var/tmp/file';

        return $parameters;
    }
}
