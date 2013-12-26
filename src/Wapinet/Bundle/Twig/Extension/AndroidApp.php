<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;

class AndroidApp extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('wapinet_android_app_screenshot', array($this, 'getScreenshot')),
        );
    }

    /**
     * @param string $path
     * @return string|null
     */
    public function getScreenshot($path)
    {
        return null;
    }


    /**
     * @return string
     */
    protected function getWebDir()
    {
        return $this->container->get('kernel')->getWebDir();
    }

    /**
     * @return string
     */
    protected function getTmpDir()
    {
        return $this->container->get('kernel')->getTmpFileDir();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_android_app';
    }
}
