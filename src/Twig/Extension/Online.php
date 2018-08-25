<?php

namespace App\Twig\Extension;

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
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('wapinet_online', function () {
                return $this->em->createQuery('SELECT COUNT(o.id) FROM App\Entity\Online o')->getSingleScalarResult();
            }),
        ];
    }
}
