<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener(priority: -1)]
class OnlineListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        // чистим случайным образом, чтобы разгрузить БД
        if (1 === \mt_rand(1, 10)) {
            //$this->entityManager->getConnection()->executeStatement('DELETE FROM online WHERE DATE_SUB(NOW(), INTERVAL '.User::LIFETIME.') > datetime');
        }

        $request = $event->getRequest();

        $requestIp = $request->getClientIp();
        $requestBrowser = $request->headers->get('User-Agent', '');

        $online = $this->entityManager->getConnection()->executeQuery(
            'SELECT id, datetime, path FROM online WHERE ip = :ip AND browser = :browser',
            ['ip' => $requestIp, 'browser' => $requestBrowser],
            [Types::STRING, Types::STRING]
        )->fetchAssociative();

        try {
            if ($online) {
                $this->entityManager->getConnection()->executeStatement(
                    'UPDATE online SET datetime = NOW(), path = :path WHERE id = :id',
                    ['path' => $request->getPathInfo(), 'id' => $online['id']],
                    [Types::STRING, Types::INTEGER]
                );
            } else {
                $this->entityManager->getConnection()->executeStatement(
                    'INSERT INTO online SET datetime = NOW(), ip = :ip, browser = :browser, path = :path',
                    ['ip' => $requestIp, 'browser' => $requestBrowser, 'path' => $request->getPathInfo()],
                    [Types::STRING, Types::STRING, Types::STRING]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Не удалось записать online', ['exception' => $e]);
        }
    }
}
