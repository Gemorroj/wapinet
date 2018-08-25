<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Helper\Timezone;
use App\Repository\FileRepository;
use Doctrine\ORM\EntityManagerInterface;

class File extends \Twig_Extension
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
            new \Twig_SimpleFilter('basename', 'basename'),
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
            new \Twig_SimpleFunction('file_count_all', [$this, 'getCountAll']),
            new \Twig_SimpleFunction('file_count_today', [$this, 'getCountToday']),
            new \Twig_SimpleFunction('file_count_hidden', [$this, 'getCountHidden']),
            new \Twig_SimpleFunction('file_count_yesterday', [$this, 'getCountYesterday']),
            new \Twig_SimpleFunction('file_count_category', [$this, 'getCountCategory']),
            new \Twig_SimpleFunction('file_count_user', [$this, 'getCountUser']),
        ];
    }

    /**
     * @return int
     */
    public function getCountAll(): int
    {
        return $this->fileRepository->countAll();
    }

    /**
     * @return int
     */
    public function getCountToday(): int
    {
        return $this->fileRepository->countDate(
            new \DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    /**
     * @return int
     */
    public function getCountYesterday(): int
    {
        return $this->fileRepository->countDate(
            new \DateTime('yesterday', $this->timezoneHelper->getTimezone()),
            new \DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    /**
     * @return int
     */
    public function getCountHidden(): int
    {
        return $this->fileRepository->countHidden();
    }

    /**
     * @param string $category
     *
     * @return int
     */
    public function getCountCategory(string $category): int
    {
        return $this->fileRepository->countCategory($category);
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function getCountUser(User $user): int
    {
        return $this->fileRepository->countUser($user);
    }
}
