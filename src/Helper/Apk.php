<?php

namespace App\Helper;

use ApkParser\Parser;

/**
 * Apk хэлпер
 */
class Apk
{
    /**
     * @var Parser
     */
    protected $apk;

    /**
     * @param string $apkPath
     *
     * @return Apk
     */
    public function init($apkPath)
    {
        $this->apk = new Parser($apkPath);

        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->getManifest()->getApplication()->getIcon();
    }

    /**
     * @return \ApkParser\Manifest
     */
    public function getManifest()
    {
        return $this->apk->getManifest();
    }
}
