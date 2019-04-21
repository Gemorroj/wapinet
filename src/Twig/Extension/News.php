<?php

namespace App\Twig\Extension;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class News extends AbstractExtension
{
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
        return [
            new TwigFunction('news_last_date', [$this, 'getLastDate']),
        ];
    }

    /**
     * @return DateTime|null
     */
    public function getLastDate()
    {
        $result = $this->em->getRepository(\App\Entity\News::class)->getLastDate()->getOneOrNullResult();

        return null === $result ? null : $result['createdAt'];
    }
}
