<?php

declare(strict_types=1);

namespace App\Service;

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

class CaptchaService
{
    public const string SESSION_KEY = '_app_captcha';

    public function generateGd(string $phrase, int $width = 200, int $height = 46, ?string $font = null): \GdImage
    {
        $builder = new CaptchaBuilder($phrase);
        /** @var \GdImage $gd */
        $gd = $builder->build(
            $width,
            $height,
            $font,
        )->getGd();

        return $gd;
    }

    public function generatePhrase(int $length = 5): string
    {
        return new PhraseBuilder($length)->build();
    }
}
