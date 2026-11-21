<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Message\RequestMessage;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: RequestEvent::class)]
final readonly class RequestListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private MessageBusInterface $messageBus,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = (string) $request->attributes->get('_route', '');
        if (\str_starts_with($routeName, '_')) {
            return;
        }

        $userIdentifier = $this->tokenStorage->getToken()?->getUser()?->getUserIdentifier();

        if (null === $userIdentifier) {
            return;
        }

        $this->messageBus->dispatch(new RequestMessage(
            $this->clock->now(),
            $request->getClientIp() ?? '',
            $request->headers->get('User-Agent', ''),
            $request->getPathInfo(),
            $userIdentifier,
        ));
    }
}
