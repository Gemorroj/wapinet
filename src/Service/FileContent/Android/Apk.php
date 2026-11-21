<?php

namespace App\Service\FileContent\Android;

use ApkParser\Parser;

final class Apk
{
    private Parser $apk;

    public function init(string $apkPath): self
    {
        // Modern APKs reference the icon by a resource id, so the resource table has to be parsed.
        $this->apk = new Parser($apkPath, ['manifest_only' => false]);

        return $this;
    }

    public function getIcon(): string
    {
        return $this->getManifest()->getApplication()->getIcon();
    }

    /**
     * Icon entries of the APK. Legacy applications store the file path in the
     * manifest, modern ones a resource id that resolves to a list of densities.
     *
     * @return string[]
     */
    public function getIconEntries(): array
    {
        $icon = $this->getIcon();

        if ('' === $icon) {
            return [];
        }

        if (!\str_starts_with($icon, '0x')) {
            return [$icon];
        }

        $entries = $this->apk->getResources($icon);

        return \is_array($entries) ? \array_values(\array_filter($entries, \is_string(...))) : [];
    }

    public function getManifest(): \ApkParser\Manifest
    {
        return $this->apk->getManifest();
    }
}
