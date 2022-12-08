<?php

namespace App\Twig\Extension;

use App\Repository\GuestbookRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Guestbook extends AbstractExtension
{
    public function __construct(private GuestbookRepository $guestbookRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('guestbook_count_all', function () {
                return $this->guestbookRepository->countAll();
            }),
        ];
    }
}
