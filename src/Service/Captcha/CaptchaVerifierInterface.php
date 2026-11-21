<?php

namespace App\Service\Captcha;

interface CaptchaVerifierInterface
{
    public function isEnabled(): bool;

    public function getSiteKey(): string;

    /**
     * @throws CaptchaUnavailableException when the captcha provider cannot be reached
     */
    public function verify(string $token, ?string $ip): bool;
}
