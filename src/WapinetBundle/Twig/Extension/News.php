<?php

namespace WapinetBundle\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class News extends \Twig_Extension
{
    protected $container;
    protected $em;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em)
    {
        $this->container = $container;
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
            new \Twig_SimpleFunction('news_last_date', array($this, 'getLastDate')),
        );
    }

    /**
     * @return \DateTime|null
     */
    public function getLastDate()
    {
        $result = $this->em->getRepository(\WapinetBundle\Entity\News::class)->getLastDate()->getOneOrNullResult();

        return (null === $result ? null : $result['createdAt']);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'news';
    }
}
