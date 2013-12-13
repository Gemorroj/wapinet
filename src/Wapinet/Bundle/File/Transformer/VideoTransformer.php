<?php

namespace Wapinet\Bundle\File\Transformer;

use Liip\ImagineBundle\Imagine\Data\Transformer\TransformerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
use FFMpeg\Coordinate\TimeCode;

class VideoTransformer implements TransformerInterface
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
     * {@inheritDoc}
     */
    public function apply($absolutePath)
    {
        $file = new File($absolutePath);
        $screenshot = $absolutePath;

        if (0 === strpos($file->getMimeType(), 'video/')) {
            $screenshot .= '.' . $this->container->getParameter('wapinet_video_screenshot_extension');

            if (false === file_exists($screenshot)) {
                $ffmpeg = $this->container->get('dubture_ffmpeg.ffmpeg');
                $second = $this->container->getParameter('wapinet_video_screenshot_second');

                $ffmpeg
                    ->open($absolutePath)
                    ->frame(TimeCode::fromSeconds($second))
                    ->save($screenshot);
            }
        }

        return $screenshot;
    }
}
