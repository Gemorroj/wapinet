<?php

namespace App\Twig\Extension;

use App\Service\Apk;
use App\Service\Archiver\ArchiveZip;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File as BaseFile;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AndroidApp extends AbstractExtension
{
    public function __construct(
        private readonly ArchiveZip $archiveZip,
        private readonly Apk $apk,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
        private readonly ParameterBagInterface $parameterBag
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_android_app_screenshot', $this->getScreenshot(...)),
        ];
    }

    public function getScreenshot(string $path): ?string
    {
        $screenshot = $path.'.png';

        if (!$this->filesystem->exists($this->getPublicDir().$screenshot)) {
            $issetIcon = $this->findIcon($path, $screenshot);

            if (true !== $issetIcon) {
                $screenshot = null;
            }
        }

        return $screenshot;
    }

    private function findIcon(string $path, string $screenshot): bool
    {
        try {
            $this->apk->init($this->getPublicDir().$path);
            $icon = $this->apk->getIcon();

            if ($icon && $this->extractIcon($icon, $path, $screenshot)) {
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
                if ($this->extractIcon('res'.$dir.'/'.$icon, $path, $screenshot)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function extractIcon(string $icon, string $path, string $screenshot): bool
    {
        try {
            $this->archiveZip->extractEntry(
                new BaseFile($this->getPublicDir().$path, false),
                $icon,
                $this->getTmpDir()
            );
            $this->filesystem->rename(
                $this->getTmpDir().'/'.$icon,
                $this->getPublicDir().$screenshot
            );

            return true;
        } catch (\Exception $e) {
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
