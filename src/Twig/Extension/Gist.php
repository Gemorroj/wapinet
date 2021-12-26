<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\GistRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Gist extends AbstractExtension
{
    private GistRepository $gistRepository;

    public function __construct(GistRepository $gistRepository)
    {
        $this->gistRepository = $gistRepository;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gist_count_all', [$this, 'getCountAll']),
            new TwigFunction('gist_count', [$this, 'getCount']),
        ];
    }

    public function getCountAll(): int
    {
        return $this->gistRepository->countAll();
    }

    public function getCount(User $user = null): int
    {
        return $this->gistRepository->countUser($user);
    }
}
