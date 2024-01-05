<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Message\RequestMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: RequestEvent::class)]
readonly class RequestListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private MessageBusInterface $messageBus
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route', '');
        if ('fos_js_routing_js' === $routeName || '_' === $routeName[0]) {
            return;
        }

        $userIdentifier = $this->tokenStorage->getToken()?->getUser()?->getUserIdentifier();
        $this->messageBus->dispatch(new RequestMessage(
            new \DateTime(),
            $request->getClientIp() ?? '',
            $request->headers->get('User-Agent', ''),
            $request->getPathInfo(),
            $userIdentifier,
        ));
    }
}
