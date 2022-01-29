<?php

namespace App\Twig\Extension;

use App\Entity\User;
use App\Entity\UserPanel as UserPanel;
use ArrayIterator;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Panel extends AbstractExtension
{
    private TokenStorageInterface $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('wapinet_panel', [$this, 'getPanel']),
        ];
    }

    public function getPanel(array $options = []): ArrayIterator
    {
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() && $token->getUser() instanceof User) {
            $panel = $token->getUser()->getPanel();
        } else {
            $panel = new UserPanel();
        }

        return $panel->getIterator();
    }
}
