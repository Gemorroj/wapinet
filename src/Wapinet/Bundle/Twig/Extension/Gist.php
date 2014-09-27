<?php

namespace Wapinet\Bundle\Twig\Extension;

use Doctrine\ORM\EntityManager;
use Wapinet\Bundle\Entity\GistRepository;
use Wapinet\UserBundle\Entity\User;

class Gist extends \Twig_Extension
{
    /**
     * @var GistRepository
     */
    protected $gistRepository;

    public function __construct(EntityManager $em)
    {
        $this->gistRepository = $em->getRepository('Wapinet\Bundle\Entity\Gist');
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
            new \Twig_SimpleFunction('gist_comments_count', array($this, 'getCommentsCount')),
        );
    }

    public function getCountAll()
    {
        return $this->gistRepository->countAll();
    }

    /**
     * @param User $user
     *
     * @return int
     */
    public function getCount(User $user = null)
    {
        return $this->gistRepository->count($user);
    }


    /**
     * @param int $id
     *
     * @return int
     */
    public function getCommentsCount($id)
    {
        return $this->gistRepository->countComments($id);
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
