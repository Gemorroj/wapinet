<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Writable temporary directories, relative to the var/ directory.
     */
    private const array TMP_DIRECTORIES = [
        'kernel.tmp_dir' => 'tmp',
        'kernel.tmp_archiver_dir' => 'tmp/archiver',
        'kernel.tmp_audio_tags_dir' => 'tmp/audio-tags',
        'kernel.tmp_file_dir' => 'tmp/file',
    ];

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }

    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();

        foreach (self::TMP_DIRECTORIES as $dir) {
            $path = $this->getProjectDir().'/var/'.$dir;

            if (!\is_dir($path)) {
                if (!@\mkdir($path, 0o777, true) && !\is_dir($path)) {
                    throw new \RuntimeException(\sprintf('Unable to create the "var/%s" directory (%s).', $dir, $path));
                }
            } elseif (!\is_writable($path)) {
                throw new \RuntimeException(\sprintf('Unable to write in the "var/%s" directory (%s).', $dir, $path));
            }
        }

        return $container;
    }

    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        if (($allowedEnvs = $this->getAllowedEnvs()) && !\in_array($this->environment, $allowedEnvs, true)) {
            throw new \InvalidArgumentException(\sprintf('The environment "%s" is not registered as allowed by "%s::getAllowedEnvs()".', $this->environment, static::class));
        }

        foreach (self::TMP_DIRECTORIES as $name => $dir) {
            $parameters[$name] = $this->getProjectDir().'/var/'.$dir;
        }

        return $parameters;
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
