<?php

namespace App\Service\Api\File;

use App\Entity\User;
use App\Service\Api\RateLimit;
use App\Service\Captcha\CaptchaUnavailableException;
use App\Service\Captcha\CaptchaVerifierInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class FileUploadGuard
{
    public function __construct(
        private CaptchaVerifierInterface $captchaVerifier,
        #[Autowire(service: 'limiter.api_files_upload')]
        private RateLimiterFactory $uploadLimiter,
    ) {
    }

    public function assertCanUpload(Request $request, ?User $user, ?string $captchaToken): void
    {
        RateLimit::consumeForVisitor($this->uploadLimiter, $request, $user);

        if (null !== $user) {
            return;
        }

        try {
            $captchaValid = $this->captchaVerifier->verify($captchaToken ?? '', $request->getClientIp());
        } catch (CaptchaUnavailableException $exception) {
            throw new ServiceUnavailableHttpException(null, 'Сервис капчи недоступен.', $exception);
        }

        if (!$captchaValid) {
            throw new AccessDeniedHttpException('Проверка капчи не пройдена.');
        }
    }
}

