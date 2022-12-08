<?php

namespace App\Twig\Extension;

use App\Repository\NewsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class News extends AbstractExtension
{
    public function __construct(private NewsRepository $newsRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('news_last_date', [$this, 'getLastDate']),
        ];
    }

    public function getLastDate(): ?\DateTime
    {
        $lastNews = $this->newsRepository->getLastNews();
        if ($lastNews) {
            return $lastNews->getCreatedAt();
        }

        return null;
    }
}
