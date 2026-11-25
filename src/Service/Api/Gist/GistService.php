<?php

declare(strict_types=1);

namespace App\Service\Api\Gist;

use App\Dto\Api\Gist\GistCreateRequest;
use App\Dto\Api\Gist\GistUpdateRequest;
use App\Entity\Gist;
use App\Entity\User;
use App\Exception\PublicHttpException;
use App\Message\GistAddMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class GistService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function create(GistCreateRequest $payload, Request $request, User $user): Gist
    {
        if ('' !== \trim((string) $payload->bCheck)) {
            throw new PublicHttpException(403, 'Spam detected.');
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
