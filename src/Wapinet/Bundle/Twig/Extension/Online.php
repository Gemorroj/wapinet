<?php

namespace Wapinet\Bundle\Twig\Extension;

use Doctrine\ORM\EntityManager;

class Online extends \Twig_Extension
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_online', array($this, 'getOnline')),
        );
    }

    /**
     * @return int
     */
    public function getOnline()
    {
        return $this->em->createQuery('SELECT COUNT(o.id) FROM Wapinet\Bundle\Entity\Online o')->getSingleScalarResult();
    }


    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_online';
    }
}
