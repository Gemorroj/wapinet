<?php

declare(strict_types=1);

namespace App\Service\FileContent;

use App\Entity\File;
use App\Enum\FileMime;
use App\Service\FileContent\Android\AndroidApp;
use App\Service\FileContent\Audio\AudioProcessor;
use App\Service\FileContent\Java\JavaApp;
use App\Service\FileContent\Video\VideoProcessor;
use App\Service\FileSystem\Storage;

/**
 * Source picture that represents a file in lists and on the detail page.
 *
 * The HTTP layer only reads ready-made previews: generation is done in the background.
 */
class PreviewGenerator
{
    /**
     * Marker file for files that legally have no preview (for example a JAR
     * without an icon), so the worker does not try to extract it again.
     */
    private const string MISSING_SUFFIX = '.no-preview';

    public function __construct(
        private readonly VideoProcessor $video,
        private readonly AudioProcessor $audio,
        private readonly JavaApp $javaApp,
        private readonly AndroidApp $androidApp,
        private readonly Storage $storage,
    ) {
    }

    /**
     * Whether the file can have a generated preview at all.
     */
    public function supports(File $file): bool
    {
        return $file->isVideo() || $file->isAudio() || $file->isJavaApp() || $file->isAndroidApp();
    }

    /**
     * Existing preview of the file, or null when there is nothing to show.
     */
    public function source(File $file, string $url): ?string
    {
        if ('' === $url) {
            return null;
        }

        if ($file->isImage()) {
            return \in_array($file->getMimeType(), FileMime::IMAGE_THUMBNAIL, true) ? $url : null;
        }

        return $this->supports($file) ? $this->find($file, $url) : null;
    }

    /**
     * Whether the worker still has to try to build a preview.
     */
    public function needsGeneration(File $file, string $url): bool
    {
        return '' !== $url
            && $this->supports($file)
            && null === $this->find($file, $url)
            && !$this->isMissing($url);
    }

    /**
     * Existing preview of the file, without generating anything.
     */
    public function find(File $file, string $url): ?string
    {
        return match (true) {
            $file->isVideo() => $this->video->find($url),
            $file->isAudio() => $this->audio->find($url),
            $file->isJavaApp() => $this->javaApp->find($url),
            $file->isAndroidApp() => $this->androidApp->find($url),
            default => null,
        };
    }

    /**
     * Creates the preview next to the file. Used by the background worker only.
     */
    public function generate(File $file, string $url): void
    {
        if ('' === $url || !$this->supports($file) || $this->isMissing($url)) {
            return;
        }

        try {
            match (true) {
                $file->isVideo() => $this->video->getScreenshot($url),
                $file->isAudio() => $this->audio->getCover($url),
                $file->isJavaApp() => $this->javaApp->getScreenshot($url),
                $file->isAndroidApp() => $this->androidApp->getScreenshot($url),
            };
        } catch (\Throwable) {
            // Previews are best-effort: the page simply stays without a picture.
        }

        if (null === $this->find($file, $url)) {
            $this->markMissing($url);
        }
    }

    private function isMissing(string $url): bool
    {
        return $this->storage->has($url.self::MISSING_SUFFIX);
    }

    private function markMissing(string $url): void
    {
        @\file_put_contents($this->storage->absolute($url.self::MISSING_SUFFIX), '');
    }
}
