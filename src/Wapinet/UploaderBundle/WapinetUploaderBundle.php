<?php

namespace Wapinet\UploaderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wapinet\UploaderBundle\DependencyInjection\Compiler\FileUrlCompilerPass;

class WapinetUploaderBundle extends Bundle
{
    public function getParent()
    {
        return 'VichUploaderBundle';
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FileUrlCompilerPass());
    }
}