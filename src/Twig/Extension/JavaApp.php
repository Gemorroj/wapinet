<?php

namespace App\Twig\Extension;

use App\Exception\ArchiverException;
use App\Service\Archiver\ArchiveZip;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class JavaApp extends AbstractExtension
{
    public function __construct(
        private readonly ArchiveZip $archiveZip,
        private readonly Filesystem $filesystem,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_java_app_screenshot', $this->getScreenshot(...)),
        ];
    }

    public function getScreenshot(string $path): ?string
    {
        $screenshot = $path.'.png';

        if (!$this->filesystem->exists($this->getPublicDir().$screenshot)) {
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

    private function findManifestIcon(string $manifestContent, string $path, string $screenshot): bool
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
     * @throws ArchiverException
     */
    private function extractManifest(string $path): void
    {
        $this->archiveZip->extractEntry(
            new BaseFile($this->getPublicDir().$path, false),
            'META-INF/MANIFEST.MF',
            $this->getTmpDir()
        );
    }

    private function getManifestContent(string $path): ?string
    {
        try {
            $this->extractManifest($path);

            $file = new BaseFile($this->getTmpDir().'/META-INF/MANIFEST.MF');
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

    private function extractManifestIcon(string $content, string $path, string $screenshot): bool
    {
        foreach (\explode(',', $content) as $v) {
            $v = \trim(\trim($v), '/');
            if ('png' === \strtolower(\pathinfo($v, \PATHINFO_EXTENSION))) {
                try {
                    $this->archiveZip->extractEntry(
                        new BaseFile($this->getPublicDir().$path, false),
                        $v,
                        $this->getTmpDir()
                    );
                    $this->filesystem->rename(
                        $this->getTmpDir().'/'.$v,
                        $this->getPublicDir().$screenshot
                    );

                    return true;
                    break;
                } catch (\Exception $e) {
                }
            }
        }

        return false;
    }

    private function getPublicDir(): string
    {
        return $this->parameterBag->get('kernel.project_dir').'/public';
    }

    private function getTmpDir(): string
    {
        return $this->parameterBag->get('kernel.tmp_file_dir');
    }
}
