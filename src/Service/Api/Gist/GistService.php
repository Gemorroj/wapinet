<?php

declare(strict_types=1);

namespace App\Service\Api\Gist;

use App\Dto\Api\Gist\GistCreateRequest;
use App\Dto\Api\Gist\GistUpdateRequest;
use App\Entity\Gist;
use App\Entity\User;
use App\Message\GistAddMessage;
use App\Service\Api\RateLimit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class GistService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        #[Autowire(service: 'limiter.api_gists_create')]
        private RateLimiterFactory $gistLimiter,
    ) {
    }

    public function create(GistCreateRequest $payload, Request $request, User $user): Gist
    {
        RateLimit::consumeForUser($this->gistLimiter, $user);

        if ('' !== \trim((string) $payload->bCheck)) {
            throw new AccessDeniedHttpException('Spam detected.');
        }

        $gist = new Gist();
        $gist->setSubject($payload->subject);
        $gist->setBody($payload->body);
        $gist->setUser($user);
        $gist->setIp($request->getClientIp() ?? '');
        $gist->setBrowser((string) $request->headers->get('User-Agent', ''));

        $this->entityManager->persist($gist);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new GistAddMessage((int) $gist->getId()));

        return $gist;
    }

    public function update(Gist $gist, GistUpdateRequest $payload): void
    {
        $gist->setSubject($payload->subject);
        $gist->setBody($payload->body);
        $gist->setUpdatedAtValue();

        $this->entityManager->persist($gist);
        $this->entityManager->flush();
    }

    public function delete(Gist $gist): void
    {
        $this->entityManager->remove($gist);
        $this->entityManager->flush();
    }
}
