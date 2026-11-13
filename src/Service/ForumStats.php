<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ForumStats
{
    public function __construct(
        #[Autowire('%env(FORUM_DATABASE_NAME)%')]
        private string $database,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function getTopicsCount(): ?int
    {
        return $this->count('topics');
    }

    public function getPostsCount(): ?int
    {
        return $this->count('posts');
    }

    private function count(string $table): ?int
    {
        if ('' === $this->database) {
            return null;
        }

        try {
            $result = $this->entityManager->getConnection()->executeQuery(\sprintf('SELECT COUNT(1) FROM `%s`.`%s`', $this->database, $table));

            return (int) $result->fetchOne();
        } catch (\Throwable $exception) {
            $this->logger->warning('Can\'t execute forum stats query.', ['exception' => $exception]);

            return null;
        }
    }
}
