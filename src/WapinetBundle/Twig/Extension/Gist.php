<?php

namespace WapinetBundle\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;
use WapinetBundle\Entity\GistRepository;
use WapinetBundle\Entity\User;

class Gist extends \Twig_Extension
{
    /**
     * @var GistRepository
     */
    protected $gistRepository;

    public function __construct(EntityManagerInterface $em)
    {
        $this->gistRepository = $em->getRepository(\WapinetBundle\Entity\Gist::class);
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('gist_count_all', array($this, 'getCountAll')),
            new \Twig_SimpleFunction('gist_count', array($this, 'getCount')),
        );
    }

    /**
     * @return int
     */
    public function getCountAll()
    {
        return $this->gistRepository->countAll();
    }

    /**
     * @param User|null $user
     *
     * @return int
     */
    public function getCount(User $user = null)
    {
        return $this->gistRepository->countUser($user);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'gist';
    }
}
