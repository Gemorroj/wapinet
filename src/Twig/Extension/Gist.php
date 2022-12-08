<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Repository\GistRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Gist extends AbstractExtension
{
    public function __construct(private GistRepository $gistRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gist_count', [$this, 'getCount']),
        ];
    }

    public function getCount(?User $user = null): int
    {
        return $this->gistRepository->countUser($user);
    }
}
