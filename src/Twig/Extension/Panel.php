<?php

namespace App\Twig\Extension;

use App\Entity\Panel as UserPanel;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Panel extends \Twig_Extension
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('wapinet_panel', [$this, 'getPanel']),
        ];
    }

    /**
     * @param array $options
     * @return \ArrayIterator
     */
    public function getPanel(array $options = [])
    {
        $token = $this->tokenStorage->getToken();

        if ($token && $token->getUser() && $token->getUser() instanceof User) {
            $panel = $token->getUser()->getPanel();
        } else {
            $panel = new UserPanel();
        }

        return $panel->getIterator();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_panel';
    }
}
