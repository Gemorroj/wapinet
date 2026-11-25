<?php

declare(strict_types=1);

namespace App\Service\Api\Guestbook;

use App\Dto\Api\Guestbook\GuestbookCreateRequest;
use App\Entity\Guestbook;
use App\Entity\User;
use App\Exception\PublicHttpException;
use App\Service\Captcha\CaptchaUnavailableException;
use App\Service\Captcha\CaptchaVerifierInterface;
use App\Service\StopSpam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GuestbookService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CaptchaVerifierInterface $captchaVerifier,
        private StopSpam $stopSpam,
    ) {
    }

    public function create(GuestbookCreateRequest $payload, Request $request, ?User $user): Guestbook
    {
        if ('' !== \trim((string) $payload->bCheck)) {
            throw new PublicHttpException(403, 'Spam detected.');
        }

        if (null === $user) {
            try {
                $captchaValid = $this->captchaVerifier->verify($payload->captchaToken ?? '', $request->getClientIp());
            } catch (CaptchaUnavailableException $exception) {
                throw new PublicHttpException(503, 'Captcha service is unavailable.', $exception);
            }

            if (!$captchaValid) {
                throw new PublicHttpException(403, 'Captcha verification failed.');
            }

            $this->stopSpam->checkRequest($request);
        }

        $message = new Guestbook();
        $message->setMessage(\trim($payload->message));
        $message->setUser($user);
        $message->setIp($request->getClientIp() ?? '');
        $message->setBrowser((string) $request->headers->get('User-Agent', ''));

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        return $message;
    }
}
