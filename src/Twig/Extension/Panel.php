<?php

namespace App\Twig\Extension;

use App\Entity\Panel as UserPanel;
use App\Entity\User;
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

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
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
