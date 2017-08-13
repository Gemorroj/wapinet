<?php

namespace WapinetBundle\Twig\Extension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use WapinetBundle\Exception\ArchiverException;

class JavaApp extends \Twig_Extension
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
            new \Twig_SimpleFilter('wapinet_java_app_screenshot', array($this, 'getScreenshot')),
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
            $manifestContent = $this->getManifestContent($path);

            $issetIcon = false;
            if (null !== $manifestContent) {
                $issetIcon = $this->findManifestIcon($manifestContent, $path, $screenshot);
            }

            if (true !== $issetIcon) {
                $screenshot = null;
            }
        }

        return $screenshot;
    }


    /**
     * @param string $manifestContent
     * @param string $path
     * @param string $screenshot
     *
     * @return bool
     */
    protected function findManifestIcon($manifestContent, $path, $screenshot)
    {
        $issetIcon = false;

        \preg_match('/MIDlet\-Icon:[\s*](.*)/iux', $manifestContent, $arr);
        if (true === isset($arr[1])) {
            $issetIcon = $this->extractManifestIcon($arr[1], $path, $screenshot);
        }

        if (false === $issetIcon) {
            \preg_match('/MIDlet\-1:[\s*](.*)/iux', $manifestContent, $arr);
            if (true === isset($arr[1])) {
                $issetIcon = $this->extractManifestIcon($arr[1], $path, $screenshot);
            }
        }

        return $issetIcon;
    }


    /**
     * @param string $path
     * @throws ArchiverException
     */
    protected function extractManifest($path)
    {
        $this->container->get('archive_zip')->extractEntry(
            new BaseFile($this->getWebDir() . $path, false),
            'META-INF/MANIFEST.MF',
            $this->getTmpDir()
        );
    }


    /**
     * @param string $path
     *
     * @return string|null
     */
    protected function getManifestContent($path)
    {
        try {
            $this->extractManifest($path);

            $file = new BaseFile($this->getTmpDir() . '/META-INF/MANIFEST.MF');
            $content = '';
            $reader = $file->openFile('r');
            while (!$reader->eof()) {
                $content .= $reader->fgets();
            }
        } catch (\Exception $e) {
            $content = null;
        }

        return $content;
    }

    /**
     * @param string $content
     * @param string $path
     * @param string $screenshot
     *
     * @return bool
     */
    protected function extractManifestIcon($content, $path, $screenshot)
    {
        foreach (\explode(',', $content) as $v) {
            $v = \trim(\trim($v), '/');
            if ('png' === \strtolower(\pathinfo($v, PATHINFO_EXTENSION))) {
                try {
                    $this->container->get('archive_zip')->extractEntry(
                        new BaseFile($this->getWebDir() . $path, false),
                        $v,
                        $this->getTmpDir()
                    );
                    $this->container->get('filesystem')->rename(
                        $this->getTmpDir() . '/' . $v,
                        $this->getWebDir() . $screenshot
                    );
                    return true;
                    break;
                } catch (\Exception $e) {}
            }
        }

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
        return 'wapinet_java_app';
    }
}
