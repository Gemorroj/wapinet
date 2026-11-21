<?php

declare(strict_types=1);

namespace App\Service\Api\Guestbook;

use App\Dto\Api\Guestbook\GuestbookCreateRequest;
use App\Entity\Guestbook;
use App\Entity\User;
use App\Service\Api\RateLimit;
use App\Service\Captcha\CaptchaUnavailableException;
use App\Service\Captcha\CaptchaVerifierInterface;
use App\Service\StopSpam;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class GuestbookService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CaptchaVerifierInterface $captchaVerifier,
        private StopSpam $stopSpam,
        #[Autowire(service: 'limiter.api_guestbook_create')]
        private RateLimiterFactory $guestbookLimiter,
    ) {
    }

    public function create(GuestbookCreateRequest $payload, Request $request, ?User $user): Guestbook
    {
        RateLimit::consumeForVisitor($this->guestbookLimiter, $request, $user);

        if ('' !== \trim((string) $payload->bCheck)) {
            throw new AccessDeniedHttpException('Spam detected.');
        }

        if (null === $user) {
            try {
                $captchaValid = $this->captchaVerifier->verify($payload->captchaToken ?? '', $request->getClientIp());
            } catch (CaptchaUnavailableException $exception) {
                throw new ServiceUnavailableHttpException(null, 'Captcha service is unavailable.', $exception);
            }

            if (!$captchaValid) {
                throw new AccessDeniedHttpException('Captcha verification failed.');
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
