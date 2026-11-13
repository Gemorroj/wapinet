<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Keeps API responses JSON and renders errors as RFC 9457 problem+json.
 *
 * The error body itself is produced by Symfony's built-in ProblemNormalizer.
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

        // ErrorRenderer uses Accept to pick the format; never fall back to HTML.
        $request->headers->set('Accept', 'application/json');
    }

    #[AsEventListener(event: KernelEvents::RESPONSE, priority: -100)]
    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!\str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $response = $event->getResponse();

        if ($response->getStatusCode() >= 400) {
            $response->headers->set('Content-Type', 'application/problem+json');
        }
    }
}
