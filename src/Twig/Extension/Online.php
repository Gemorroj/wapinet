<?php

namespace App\Twig\Extension;

use App\Repository\OnlineRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Online extends AbstractExtension
{
    private OnlineRepository $onlineRepository;

    public function __construct(OnlineRepository $onlineRepository)
    {
        $this->onlineRepository = $onlineRepository;
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
