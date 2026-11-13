<?php

namespace App\Service\Captcha;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(CaptchaVerifierInterface::class)]
final readonly class YandexCaptchaVerifier implements CaptchaVerifierInterface
{
    private const string VALIDATE_URL = 'https://smartcaptcha.cloud.yandex.ru/validate';

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'bool:CAPTCHA_ENABLED')]
        private bool $enabled,
        #[Autowire(env: 'YANDEX_SMARTCAPTCHA_SITE_KEY')]
        private string $siteKey,
        #[Autowire(env: 'YANDEX_SMARTCAPTCHA_SECRET_KEY')]
        private string $secretKey,
    ) {
        if ($enabled && ('' === \trim($siteKey) || '' === \trim($secretKey))) {
            throw new \InvalidArgumentException('Yandex SmartCaptcha keys are not configured.');
        }
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function verify(string $token, ?string $ip): bool
    {
        if (!$this->enabled) {
            return true;
        }

        $token = \trim($token);

        if ('' === $token) {
            return false;
        }

        $body = [
            'secret' => $this->secretKey,
            'token' => $token,
        ];

        if (null !== $ip && '' !== $ip) {
            $body['ip'] = $ip;
        }

        try {
            $response = $this->httpClient->request('POST', self::VALIDATE_URL, [
                'body' => $body,
                'timeout' => 5,
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new CaptchaUnavailableException('Captcha service returned an HTTP error.');
            }

            $data = $response->toArray(false);
        } catch (CaptchaUnavailableException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new CaptchaUnavailableException('Captcha service is unavailable.', 0, $exception);
        }

        return 'ok' === ($data['status'] ?? null);
    }
}
