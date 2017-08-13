<?php

namespace WapinetBundle\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;

class Online extends \Twig_Extension
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    public function __construct(EntityManagerInterface $em)
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
        return $this->em->createQuery('SELECT COUNT(o.id) FROM WapinetBundle\Entity\Online o')->getSingleScalarResult();
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
