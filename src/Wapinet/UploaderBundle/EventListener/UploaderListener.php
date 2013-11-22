<?php

namespace Wapinet\UploaderBundle\EventListener;

use Doctrine\Common\EventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vich\UploaderBundle\EventListener\UploaderListener as BaseListener;

/**
 * UploaderListener.
 */
class UploaderListener extends BaseListener
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Update the file and file name if necessary.
     *
     * @param EventArgs $args The event arguments.
     * @see https://github.com/dustin10/VichUploaderBundle/issues/141
     */
    public function preUpdate(EventArgs $args)
    {
        $form = $this->container->get('wapinet_uploader.type.file_url');
        $request = $this->container->get('request');

        $obj = $this->adapter->getObjectFromArgs($args);
        //file_put_contents('/log.log', print_r($request, true), FILE_APPEND);

        if ($this->isUploadable($obj)) {
            // $this->storage->remove($obj);
            $this->storage->upload($obj);
            
            $this->injector->injectFiles($obj);

            $this->adapter->recomputeChangeSet($args);
        }
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
