<?php

namespace Wapinet\Bundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wapinet\Bundle\DependencyInjection\Compiler\FileUrlCompilerPass;

class WapinetBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FileUrlCompilerPass());
    }
}
