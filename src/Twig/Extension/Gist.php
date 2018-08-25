<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\GistRepository;
use Doctrine\ORM\EntityManagerInterface;

class Gist extends \Twig_Extension
{
    /**
     * @var GistRepository
     */
    protected $gistRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->gistRepository = $em->getRepository(\App\Entity\Gist::class);
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('gist_count_all', [$this, 'getCountAll']),
            new \Twig_SimpleFunction('gist_count', [$this, 'getCount']),
        ];
    }

    /**
     * @return int
     */
    public function getCountAll()
    {
        return $this->gistRepository->countAll();
    }

    /**
     * @param User|null $user
     *
     * @return int
     */
    public function getCount(User $user = null)
    {
        return $this->gistRepository->countUser($user);
    }
}
