<?php

namespace Wapinet\Bundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File as BaseFile;

class AndroidApp extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * AndroidApp constructor.
     * @param ContainerInterface $container
     */
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
        $screenshot = $path . '.png';

        if (false === \file_exists($this->getWebDir() . $screenshot)) {
            $issetIcon = $this->findIcon($path, $screenshot);

            if (true !== $issetIcon) {
                $screenshot = null;
            }
        }

        return $screenshot;
    }


    /**
     * @param string $path
     * @param string $screenshot
     *
     * @return bool
     */
    protected function findIcon($path, $screenshot)
    {
        try {
            $apk = $this->container->get('apk');
            $apk->init($this->getWebDir() . $path);
            $icon = $apk->getIcon();

            if ($icon && $this->extractIcon($icon, $path, $screenshot)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->container->get('logger')->warning('Не удалось прочитать APK файл.', array($e));
        }

        $icons = array(
            'icon.png',
            'icon32.png',
            'icon24.png',
            'icon16.png',
            'app_icon.png',
            'main.png',
            'ic_launcher.png',
            'ic_app_launcher.png',
            'logo.png',
            'icn.png',
            'ic.png',
            'i.png',
            'ic_logo.png',
            'icon.PNG',
            'app_icon.PNG',
            'logo.PNG',
            'root.png',
            'apk.png',
        );
        $dirs = array(
            '',
            '/drawable-hdpi',
            '/drawable-xhdpi',
            '/drawable-xxhdpi',
            '/drawable-ldpi',
            '/drawable-mdpi',
            '/drawable',
            '/drawable-hdpi-v4',
            '/drawable-hdpi-v7',
            '/drawable-hdpi-v11',
            '/drawable-nodpi',
        );

        foreach ($dirs as $dir) {
            foreach ($icons as $icon) {
                if ($this->extractIcon('res' . $dir . '/' . $icon, $path, $screenshot)) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * @param string $icon
     * @param string $path
     * @param string $screenshot
     *
     * @return bool
     */
    protected function extractIcon($icon, $path, $screenshot)
    {
        try {
            $this->container->get('archive_zip')->extractEntry(
                new BaseFile($this->getWebDir() . $path, false),
                $icon,
                $this->getTmpDir()
            );
            $this->container->get('filesystem')->rename(
                $this->getTmpDir() . '/' . $icon,
                $this->getWebDir() . $screenshot
            );

            return true;
        } catch (\Exception $e) {}

        return false;
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
