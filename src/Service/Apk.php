<?php

namespace App\Service;

use ApkParser\Parser;

/**
 * Apk хэлпер
 */
class Apk
{
    /**
     * @var Parser
     */
    private $apk;

    public function init(string $apkPath): self
    {
        $this->apk = new Parser($apkPath);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->getManifest()->getApplication()->getIcon();
    }

    public function getManifest(): \ApkParser\Manifest
    {
        return $this->apk->getManifest();
    }
}
