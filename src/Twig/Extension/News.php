<?php

namespace App\Twig\Extension;

use App\Repository\NewsRepository;
use DateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class News extends AbstractExtension
{
    private NewsRepository $newsRepository;

    public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('news_last_date', [$this, 'getLastDate']),
        ];
    }

    public function getLastDate(): ?DateTime
    {
        $lastNews = $this->newsRepository->getLastNews();
        if ($lastNews) {
            return $lastNews->getCreatedAt();
        }

        return null;
    }
}
