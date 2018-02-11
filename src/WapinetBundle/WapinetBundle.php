<?php

namespace WapinetBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WapinetBundle\DependencyInjection\Compiler\FileUrlCompilerPass;

class WapinetBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FileUrlCompilerPass());
    }
}
