<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\GistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Gist extends AbstractExtension
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
            new TwigFunction('gist_count_all', [$this, 'getCountAll']),
            new TwigFunction('gist_count', [$this, 'getCount']),
        ];
    }

    /**
     * @return int
     */
    public function getCountAll(): int
    {
        return $this->gistRepository->countAll();
    }

    /**
     * @param User|null $user
     *
     * @return int
     */
    public function getCount(User $user = null): int
    {
        return $this->gistRepository->countUser($user);
    }
}
