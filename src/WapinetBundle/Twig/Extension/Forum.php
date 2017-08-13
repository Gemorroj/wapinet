<?php

namespace WapinetBundle\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Forum extends \Twig_Extension
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
            new \Twig_SimpleFunction('wapinet_forum_topics_count', array($this, 'getTopicsCount')),
            new \Twig_SimpleFunction('wapinet_forum_posts_count', array($this, 'getPostsCount')),
        );
    }

    /**
     * @return int
     */
    public function getTopicsCount()
    {
        $database = $this->container->getParameter('wapinet_forum_database_name');
        $query = $this->em->getConnection()->executeQuery("SELECT COUNT(1) FROM `{$database}`.`topics`");
        $query->execute();
        return $query->fetchColumn(0);
    }

    /**
     * @return int
     */
    public function getPostsCount()
    {
        $database = $this->container->getParameter('wapinet_forum_database_name');
        $query = $this->em->getConnection()->executeQuery("SELECT COUNT(1) FROM `{$database}`.`posts`");
        $query->execute();
        return $query->fetchColumn(0);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_forum';
    }
}
