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

        if (false === file_exists($this->getWebDir() . $screenshot)) {
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
        $issetIcon = $this->extractIcon('res/icon.png', $path, $screenshot);
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/ic_launcher.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-hdpi/icon.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-hdpi/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-hdpi/ic_launcher.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-xhdpi/icon.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-xhdpi/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-xhdpi/ic_launcher.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-xxhdpi/icon.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-xxhdpi/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-xxhdpi/ic_launcher.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-ldpi/icon.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-ldpi/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-ldpi/ic_launcher.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-mdpi/icon.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-mdpi/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable-mdpi/ic_launcher.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable/icon.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable/main.png', $path, $screenshot);
        }
        if (false === $issetIcon) {
            $issetIcon = $this->extractIcon('res/drawable/ic_launcher.png', $path, $screenshot);
        }

        return $issetIcon;
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
            $this->container->get('archive_7z')->extractEntry(
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
