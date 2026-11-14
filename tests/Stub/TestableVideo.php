<?php

declare(strict_types=1);

namespace App\Tests\Stub;

use App\Service\FileContent\Video\VideoProcessor;
use FFMpeg\Format\Video\DefaultVideo;
use FFMpeg\Media\AbstractStreamableMedia;

/**
 * Exposes the protected video encoding options to the tests.
 */
final class TestableVideo extends VideoProcessor
{
    public function apply(DefaultVideo $format, AbstractStreamableMedia $media): void
    {
        $this->setOptions($format, $media);
    }
}
