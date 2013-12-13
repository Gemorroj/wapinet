<?php

namespace Wapinet\Bundle\Twig\Extension;

use FFMpeg\FFProbe\DataMapping\Stream;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;

class Audio extends \Twig_Extension
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
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_audio_info', array($this, 'getInfo')),
        );
    }


    /**
     * @param File $file
     *
     * @return Stream
     */
    public function getInfo (File $file)
    {
        $ffprobe = $this->container->get('dubture_ffmpeg.ffprobe');

        return $ffprobe->streams($file->getPathname())->audios()->first();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_audio';
    }
}
