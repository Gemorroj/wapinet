<?php

namespace WapinetUploaderBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


class FileUrlCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resources = $container->getParameter('twig.form.resources');
        $resources[] = 'WapinetUploaderBundle::file_url.html.twig';

        $container->setParameter('twig.form.resources', $resources);
    }
}
