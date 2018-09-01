<?php

namespace App\Twig\Extension;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class Forum extends \Twig_Extension
{
    protected $parameterBag;
    protected $em;

    public function __construct(ParameterBagInterface $parameterBag, EntityManagerInterface $em)
    {
        $this->parameterBag = $parameterBag;
        $this->em = $em;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('wapinet_forum_topics_count', [$this, 'getTopicsCount']),
            new \Twig_SimpleFunction('wapinet_forum_posts_count', [$this, 'getPostsCount']),
        ];
    }

    /**
     * @return int
     */
    public function getTopicsCount()
    {
        $database = $this->parameterBag->get('wapinet_forum_database_name');
        $query = $this->em->getConnection()->executeQuery("SELECT COUNT(1) FROM `{$database}`.`topics`");
        $query->execute();

        return $query->fetchColumn(0);
    }

    /**
     * @return int
     */
    public function getPostsCount()
    {
        $database = $this->parameterBag->get('wapinet_forum_database_name');
        $query = $this->em->getConnection()->executeQuery("SELECT COUNT(1) FROM `{$database}`.`posts`");
        $query->execute();

        return $query->fetchColumn(0);
    }
}
