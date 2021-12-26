<?php

namespace App\Twig\Extension;

use App\Repository\GuestbookRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Guestbook extends AbstractExtension
{
    private GuestbookRepository $guestbookRepository;

    public function __construct(GuestbookRepository $guestbookRepository)
    {
        $this->guestbookRepository = $guestbookRepository;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('guestbook_count_all', function () {
                return $this->guestbookRepository->countAll();
            }),
        ];
    }
}
