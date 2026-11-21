<?php

namespace App\Service\FileContent\Android;

use App\Service\Archiver\ArchiveZip;
use App\Service\FileSystem\Storage;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as BaseFile;

class AndroidApp
{
    public function __construct(
        private readonly ArchiveZip $archiveZip,
        private readonly Apk $apk,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
        private readonly Storage $storage,
        #[Autowire('%kernel.tmp_file_dir%')]
        private readonly string $tmpDir,
    ) {
    }

    /**
     * Existing icon of the application, without generating anything.
     */
    public function find(string $url): ?string
    {
        $screenshot = $url.'.png';

        return $this->storage->has($screenshot) ? $screenshot : null;
    }

    public function getScreenshot(string $url): ?string
    {
        $screenshot = $url.'.png';

        if (!$this->filesystem->exists($this->storage->absolute($screenshot))) {
            $issetIcon = $this->findIcon($url, $screenshot);

            if (true !== $issetIcon) {
                $screenshot = null;
            }
        }

        return $screenshot;
    }

    private function findIcon(string $url, string $screenshot): bool
    {
        try {
            $this->apk->init($this->storage->absolute($url));

            if ($this->extractResolvedIcon($url, $screenshot)) {
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Не удалось прочитать APK файл.', [$e]);
        }

        $icons = [
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
        ];
        $dirs = [
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
        ];

        foreach ($dirs as $dir) {
            foreach ($icons as $icon) {
                if ($this->extractIcon('res'.$dir.'/'.$icon, $url, $screenshot)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Extracts the icon referenced by the manifest. Modern APKs store it as a
     * resource id that resolves to several bitmaps for different densities, so
     * the largest bitmap is kept as the preview.
     */
    private function extractResolvedIcon(string $url, string $screenshot): bool
    {
        $best = null;
        $bestSize = 0;

        foreach ($this->apk->getIconEntries() as $entry) {
            $extension = \strtolower(\pathinfo($entry, \PATHINFO_EXTENSION));

            // Adaptive icons are XML: only the bitmap layers can be used as a preview.
            if (!\in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                continue;
            }

            try {
                $this->archiveZip->extractEntry(
                    new BaseFile($this->storage->absolute($url), false),
                    $entry,
                    $this->tmpDir
                );
            } catch (\Exception $e) {
                continue;
            }

            $tmp = $this->tmpDir.'/'.$entry;
            $size = (int) \filesize($tmp);

            if ($size > $bestSize) {
                if (null !== $best) {
                    @\unlink($this->tmpDir.'/'.$best);
                }

                $best = $entry;
                $bestSize = $size;
            } else {
                @\unlink($tmp);
            }
        }

        if (null === $best) {
            return false;
        }

        $this->filesystem->rename($this->tmpDir.'/'.$best, $this->storage->absolute($screenshot));

        return true;
    }

    private function extractIcon(string $icon, string $url, string $screenshot): bool
    {
        try {
            $this->archiveZip->extractEntry(
                new BaseFile($this->storage->absolute($url), false),
                $icon,
                $this->tmpDir
            );
            $this->filesystem->rename(
                $this->tmpDir.'/'.$icon,
                $this->storage->absolute($screenshot)
            );

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }
}
