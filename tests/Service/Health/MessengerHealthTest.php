<?php

declare(strict_types=1);

namespace App\Tests\Service\Health;

use App\Service\Health\MessengerHealth;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

final class MessengerHealthTest extends TestCase
{
    public function testCountsMessages(): void
    {
        $health = new MessengerHealth($this->transport(4), $this->transport(2));

        static::assertSame(['async' => 4, 'failed' => 2], $health->counts());
    }

    public function testReturnsNullWhenTransportCannotCount(): void
    {
        $transport = new class implements TransportInterface {
            public function get(): iterable
            {
                return [];
            }

            public function ack(Envelope $envelope): void
            {
            }

            public function reject(Envelope $envelope): void
            {
            }

            public function send(Envelope $envelope): Envelope
            {
                return $envelope;
            }
        };

        static::assertSame(['async' => null, 'failed' => null], new MessengerHealth($transport, $transport)->counts());
    }

    public function testReturnsNullWhenCountingFails(): void
    {
        $transport = new class implements TransportInterface, MessageCountAwareInterface {
            public function get(): iterable
            {
                return [];
            }

            public function ack(Envelope $envelope): void
            {
            }

            public function reject(Envelope $envelope): void
            {
            }

            public function send(Envelope $envelope): Envelope
            {
                return $envelope;
            }

            public function getMessageCount(): int
            {
                throw new \RuntimeException('Database is unavailable.');
            }
        };

        static::assertSame(['async' => null, 'failed' => null], new MessengerHealth($transport, $transport)->counts());
    }

    private function transport(int $count): TransportInterface
    {
        return new class($count) implements TransportInterface, MessageCountAwareInterface {
            public function __construct(private readonly int $count)
            {
            }

            public function get(): iterable
            {
                return [];
            }

            public function ack(Envelope $envelope): void
            {
            }

            public function reject(Envelope $envelope): void
            {
            }

            public function send(Envelope $envelope): Envelope
            {
                return $envelope;
            }

            public function getMessageCount(): int
            {
                return $this->count;
            }
        };
    }
}
