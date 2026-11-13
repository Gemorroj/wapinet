<?php

namespace App\Service\FileContent;

use App\Service\Archiver\ArchiverException;
use App\Service\Archiver\ArchiveZip;
use App\Service\FileSystem\Storage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as BaseFile;

class JavaApp
{
    public function __construct(
        private readonly ArchiveZip $archiveZip,
        private readonly Filesystem $filesystem,
        private readonly Storage $storage,
        #[Autowire('%kernel.tmp_file_dir%')]
        private readonly string $tmpDir,
    ) {
    }

    /**
     * Existing icon of the archive, without generating anything.
     */
    public function find(string $url): ?string
    {
        $screenshot = $url.'.png';

        return $this->storage->has($screenshot) ? $screenshot : null;
    }

    public function getScreenshot(string $url): ?string
    {
        $screenshot = $url.'.png';

        if ($this->filesystem->exists($this->storage->absolute($screenshot))) {
            return $screenshot;
        }

        $manifestContent = $this->getManifestContent($url);

        if (null !== $manifestContent && $this->findManifestIcon($manifestContent, $url, $screenshot)) {
            return $screenshot;
        }

        return null;
    }

    private function findManifestIcon(string $manifestContent, string $url, string $screenshot): bool
    {
        $issetIcon = false;

        \preg_match('/MIDlet\-Icon:[\s*](.*)/iux', $manifestContent, $arr);
        if (true === isset($arr[1])) {
            $issetIcon = $this->extractManifestIcon($arr[1], $url, $screenshot);
        }

        if (false === $issetIcon) {
            \preg_match('/MIDlet\-1:[\s*](.*)/iux', $manifestContent, $arr);
            if (true === isset($arr[1])) {
                $issetIcon = $this->extractManifestIcon($arr[1], $url, $screenshot);
            }
        }

        return $issetIcon;
    }

    /**
     * @throws ArchiverException
     */
    private function extractManifest(string $url): void
    {
        $this->archiveZip->extractEntry(
            new BaseFile($this->storage->absolute($url), false),
            'META-INF/MANIFEST.MF',
            $this->tmpDir
        );
    }

    private function getManifestContent(string $url): ?string
    {
        try {
            $this->extractManifest($url);

            $file = new BaseFile($this->tmpDir.'/META-INF/MANIFEST.MF');
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

    private function extractManifestIcon(string $content, string $url, string $screenshot): bool
    {
        foreach (\explode(',', $content) as $v) {
            $v = \trim(\trim($v), '/');
            if ('png' === \strtolower(\pathinfo($v, \PATHINFO_EXTENSION))) {
                try {
                    $this->archiveZip->extractEntry(
                        new BaseFile($this->storage->absolute($url), false),
                        $v,
                        $this->tmpDir
                    );
                    $this->filesystem->rename(
                        $this->tmpDir.'/'.$v,
                        $this->storage->absolute($screenshot)
                    );

                    return true;
                } catch (\Exception $e) {
                }
            }
        }

        return false;
    }
}
