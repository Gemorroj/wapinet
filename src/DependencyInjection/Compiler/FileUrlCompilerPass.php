<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final readonly class FileUrlCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $resources = (array) $container->getParameter('twig.form.resources');
        $resources[] = 'file_url.html.twig';

        $container->setParameter('twig.form.resources', $resources);
    }
}
