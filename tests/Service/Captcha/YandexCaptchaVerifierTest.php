<?php

namespace App\Tests\Service\Captcha;

use App\Service\Captcha\CaptchaUnavailableException;
use App\Service\Captcha\YandexCaptchaVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class YandexCaptchaVerifierTest extends TestCase
{
    public function testVerifiesToken(): void
    {
        $verifier = new YandexCaptchaVerifier(
            new MockHttpClient(static function (string $method, string $url, array $options): MockResponse {
                self::assertSame('POST', $method);
                self::assertSame('https://smartcaptcha.cloud.yandex.ru/validate', $url);

                return new MockResponse('{"status":"ok"}');
            }),
            true,
            'site-key',
            'secret-key',
        );

        self::assertTrue($verifier->isEnabled());
        self::assertSame('site-key', $verifier->getSiteKey());
        self::assertTrue($verifier->verify('token', '127.0.0.1'));
    }

    public function testRejectsFailedToken(): void
    {
        $verifier = new YandexCaptchaVerifier(
            new MockHttpClient(new MockResponse('{"status":"failed"}')),
            true,
            'site-key',
            'secret-key',
        );

        self::assertFalse($verifier->verify('token', null));
    }

    public function testRejectsEmptyTokenWithoutRequest(): void
    {
        $verifier = new YandexCaptchaVerifier(
            new MockHttpClient(static fn (): never => throw new \LogicException('No request expected.')),
            true,
            'site-key',
            'secret-key',
        );

        self::assertFalse($verifier->verify('', null));
    }

    public function testDisabledCaptchaAllowsRequest(): void
    {
        $verifier = new YandexCaptchaVerifier(
            new MockHttpClient(static fn (): never => throw new \LogicException('No request expected.')),
            false,
            '',
            '',
        );

        self::assertFalse($verifier->isEnabled());
        self::assertTrue($verifier->verify('', null));
    }

    public function testProviderErrorIsUnavailable(): void
    {
        $verifier = new YandexCaptchaVerifier(
            new MockHttpClient(new MockResponse('', ['http_code' => 500])),
            true,
            'site-key',
            'secret-key',
        );

        $this->expectException(CaptchaUnavailableException::class);

        $verifier->verify('token', null);
    }
}
