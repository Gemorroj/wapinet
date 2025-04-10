<?php

namespace App\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Forum extends AbstractExtension
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_forum_topics_count', $this->getTopicsCount(...)),
            new TwigFunction('wapinet_forum_posts_count', $this->getPostsCount(...)),
        ];
    }

    public function getTopicsCount(): ?int
    {
        $database = $this->parameterBag->get('wapinet_forum_database_name');
        if (!$database) {
            return null;
        }

        try {
            $result = $this->em->getConnection()->executeQuery("SELECT COUNT(1) FROM `{$database}`.`topics`");

            return $result->fetchOne();
        } catch (\Throwable $e) {
            $this->logger->warning('Can\'t execute query', ['exception' => $e]);

            return null;
        }
    }

    public function getPostsCount(): ?int
    {
        $database = $this->parameterBag->get('wapinet_forum_database_name');
        if (!$database) {
            return null;
        }

        try {
            $result = $this->em->getConnection()->executeQuery("SELECT COUNT(1) FROM `{$database}`.`posts`");

            return $result->fetchOne();
        } catch (\Throwable $e) {
            $this->logger->warning('Can\'t execute query', ['exception' => $e]);

            return null;
        }
    }
}
