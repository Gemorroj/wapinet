<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Helper\Timezone;
use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class File extends AbstractExtension
{
    /**
     * @var FileRepository
     */
    protected $fileRepository;
    /**
     * @var Timezone
     */
    protected $timezoneHelper;

    /**
     * @param EntityManagerInterface $em
     * @param Timezone               $timezoneHelper
     */
    public function __construct(EntityManagerInterface $em, Timezone $timezoneHelper)
    {
        $this->fileRepository = $em->getRepository(\App\Entity\File::class);
        $this->timezoneHelper = $timezoneHelper;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('basename', 'basename'),
        ];
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
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
            new DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    public function getCountYesterday(): int
    {
        return $this->fileRepository->countDate(
            new DateTime('yesterday', $this->timezoneHelper->getTimezone()),
            new DateTime('today', $this->timezoneHelper->getTimezone())
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
