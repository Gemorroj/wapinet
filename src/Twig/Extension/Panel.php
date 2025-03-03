<?php

namespace App\Twig\Extension;

use App\Entity\UserPanel;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Panel extends AbstractExtension
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_panel', function (): \ArrayIterator {
                $panel = $this->tokenStorage->getToken()?->getUser()?->getPanel() ?: new UserPanel();

                return $panel->getIterator();
            }),
        ];
    }
}
