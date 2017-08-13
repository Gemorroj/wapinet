<?php

namespace WapinetUploaderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use WapinetUploaderBundle\DependencyInjection\Compiler\FileUrlCompilerPass;

class WapinetUploaderBundle extends Bundle
{
    /**
     * @inheritdoc
     */
    public function getParent()
    {
        return 'VichUploaderBundle';
    }

    /**
     * @inheritdoc
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new FileUrlCompilerPass());
    }
}
