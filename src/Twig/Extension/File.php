<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\FileRepository;
use App\Service\Timezone;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class File extends AbstractExtension
{
    public function __construct(private FileRepository $fileRepository, private Timezone $timezoneHelper)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', 'basename'),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('file_count_all', [$this, 'getCountAll']),
            new TwigFunction('file_count_today', [$this, 'getCountToday']),
            new TwigFunction('file_count_hidden', [$this, 'getCountHidden']),
            new TwigFunction('file_count_yesterday', [$this, 'getCountYesterday']),
            new TwigFunction('file_count_category', [$this, 'getCountCategory']),
            new TwigFunction('file_count_user', [$this, 'getCountUser']),
        ];
    }

    public function getCountAll(): int
    {
        return $this->fileRepository->countAll();
    }

    public function getCountToday(): int
    {
        return $this->fileRepository->countDate(
            new \DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    public function getCountYesterday(): int
    {
        return $this->fileRepository->countDate(
            new \DateTime('yesterday', $this->timezoneHelper->getTimezone()),
            new \DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    public function getCountHidden(): int
    {
        return $this->fileRepository->countHidden();
    }

    public function getCountCategory(string $category): int
    {
        return $this->fileRepository->countCategory($category);
    }

    public function getCountUser(User $user): int
    {
        return $this->fileRepository->countUser($user);
    }
}
