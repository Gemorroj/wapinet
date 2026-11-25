<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps API error responses JSON: the error renderer uses Accept to pick the format.
 *
 * The problem+json body and Content-Type are set by the error renderer
 * or by the API security handlers, never here.
 */
final class ApiErrorFormatListener
{
    #[AsEventListener(event: KernelEvents::REQUEST, priority: 100)]
    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!\str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $request->headers->set('Accept', 'application/json');
    }
}
