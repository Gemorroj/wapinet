<?php

namespace App\Twig\Extension;

use App\Entity\GuestbookRepository;
use Doctrine\ORM\EntityManagerInterface;

class Guestbook extends \Twig_Extension
{
    /**
     * @var GuestbookRepository
     */
    protected $guestbookRepository;

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->guestbookRepository = $em->getRepository(\App\Entity\Guestbook::class);
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('guestbook_count_all', array($this, 'getCountAll')),
        );
    }

    /**
     * @return int
     */
    public function getCountAll()
    {
        return $this->guestbookRepository->countAll();
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'guestbook';
    }
}
