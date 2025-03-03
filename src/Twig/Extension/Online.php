<?php

namespace App\Twig\Extension;

use App\Repository\OnlineRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Online extends AbstractExtension
{
    public function __construct(private readonly OnlineRepository $onlineRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_online', function (): int {
                return $this->onlineRepository->count([]);
            }),
        ];
    }
}
