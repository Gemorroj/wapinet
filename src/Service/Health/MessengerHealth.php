<?php

declare(strict_types=1);

namespace App\Service\Health;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Количество сообщений в очередях Messenger для админки.
 */
final readonly class MessengerHealth
{
    public function __construct(
        #[Autowire(service: 'messenger.transport.async')]
        private TransportInterface $asyncTransport,
        #[Autowire(service: 'messenger.transport.failed')]
        private TransportInterface $failedTransport,
    ) {
    }

    /**
     * @return array{async: int|null, failed: int|null}
     */
    public function counts(): array
    {
        return [
            'async' => $this->messageCount($this->asyncTransport),
            'failed' => $this->messageCount($this->failedTransport),
        ];
    }

    private function messageCount(TransportInterface $transport): ?int
    {
        if (!$transport instanceof MessageCountAwareInterface) {
            return null;
        }

        try {
            return $transport->getMessageCount();
        } catch (\Throwable) {
            return null;
        }
    }
}
