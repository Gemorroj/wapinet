<?php

namespace Wapinet\Bundle\Twig\Extension;

use Doctrine\ORM\EntityManager;
use Wapinet\Bundle\Entity\GuestbookRepository;

class Guestbook extends \Twig_Extension
{
    /**
     * @var GuestbookRepository
     */
    protected $guestbookRepository;

    public function __construct(EntityManager $em)
    {
        $this->guestbookRepository = $em->getRepository('Wapinet\Bundle\Entity\Guestbook');
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            'guestbook_count_all'  => new \Twig_Function_Method($this, 'getCountAll'),
        );
    }

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