<?php

namespace App\Service\Api\File;

use App\Entity\User;
use App\Exception\PublicHttpException;
use App\Service\Captcha\CaptchaUnavailableException;
use App\Service\Captcha\CaptchaVerifierInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class FileUploadGuard
{
    public function __construct(
        private CaptchaVerifierInterface $captchaVerifier,
    ) {
    }

    public function assertCanUpload(Request $request, ?User $user, ?string $captchaToken): void
    {
        if (null !== $user) {
            return;
        }

        try {
            $captchaValid = $this->captchaVerifier->verify($captchaToken ?? '', $request->getClientIp());
        } catch (CaptchaUnavailableException $exception) {
            throw new PublicHttpException(503, 'Сервис капчи недоступен.', $exception);
        }

        if (!$captchaValid) {
            throw new PublicHttpException(403, 'Проверка капчи не пройдена.');
        }
    }
}
