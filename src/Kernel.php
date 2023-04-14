<?php

namespace App;

use App\DependencyInjection\Compiler\FileUrlCompilerPass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
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

            if (!\is_dir($fullPathDir)) {
                if (false === @\mkdir($fullPathDir, 0o777, true) && !\is_dir($fullPathDir)) {
                    throw new \RuntimeException(\sprintf("Unable to create the var/%s directory\n", $dir));
                }
            } elseif (!\is_writable($fullPathDir)) {
                throw new \RuntimeException(\sprintf("Unable to write in the var/%s directory\n", $dir));
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
