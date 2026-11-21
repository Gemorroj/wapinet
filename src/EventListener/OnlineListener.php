<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\OnlineTracker;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

#[AsEventListener(event: TerminateEvent::class)]
final readonly class OnlineListener
{
    public function __construct(
        private OnlineTracker $onlineTracker,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = (string) $request->attributes->get('_route', '');

        if ('api_online_list' === $routeName || \str_starts_with($routeName, '_')) {
            return;
        }

        try {
            $this->onlineTracker->track($request);
        } catch (\Throwable $exception) {
            // An exception thrown after the response may terminate a FrankenPHP worker.
            $this->logger->error('Unable to track online activity.', ['exception' => $exception]);
        }
    }
}
