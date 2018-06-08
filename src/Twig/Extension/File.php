<?php

namespace App\Twig\Extension;

use App\Entity\FileRepository;
use App\Entity\User;
use App\Helper\Timezone;
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
     * @param Timezone $timezoneHelper
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
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('basename', 'basename'),
        );
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('file_count_all', array($this, 'getCountAll')),
            new \Twig_SimpleFunction('file_count_today', array($this, 'getCountToday')),
            new \Twig_SimpleFunction('file_count_hidden', array($this, 'getCountHidden')),
            new \Twig_SimpleFunction('file_count_yesterday', array($this, 'getCountYesterday')),
            new \Twig_SimpleFunction('file_count_category', array($this, 'getCountCategory')),
            new \Twig_SimpleFunction('file_count_user', array($this, 'getCountUser')),
        );
    }

    /**
     * @return int
     */
    public function getCountAll()
    {
        return $this->fileRepository->countAll();
    }

    /**
     * @return int
     */
    public function getCountToday()
    {
        return $this->fileRepository->countDate(
            new \DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    /**
     * @return int
     */
    public function getCountYesterday()
    {
        return $this->fileRepository->countDate(
            new \DateTime('yesterday', $this->timezoneHelper->getTimezone()),
            new \DateTime('today', $this->timezoneHelper->getTimezone())
        );
    }

    /**
     * @return int
     */
    public function getCountHidden()
    {
        return $this->fileRepository->countHidden();
    }

    /**
     * @param string $category
     * @return int
     */
    public function getCountCategory($category)
    {
        return $this->fileRepository->countCategory($category);
    }

    /**
     * @param User $user
     * @return int
     */
    public function getCountUser(User $user)
    {
        return $this->fileRepository->countUser($user);
    }
}
