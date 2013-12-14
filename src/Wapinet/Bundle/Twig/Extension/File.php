<?php

namespace Wapinet\Bundle\Twig\Extension;

use Doctrine\ORM\EntityManager;
use Wapinet\Bundle\Entity\FileRepository;

class File extends \Twig_Extension
{
    /**
     * @var FileRepository
     */
    protected $fileRepository;

    public function __construct(EntityManager $em)
    {
        $this->fileRepository = $em->getRepository('Wapinet\Bundle\Entity\File');
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            'file_count_all'  => new \Twig_Function_Method($this, 'getCountAll'),
            'file_count_today' => new \Twig_Function_Method($this, 'getCountToday'),
            'file_count_yesterday' => new \Twig_Function_Method($this, 'getCountYesterday'),
            'file_count_category' => new \Twig_Function_Method($this, 'getCountCategory'),
        );
    }

    public function getCountAll()
    {
        return $this->fileRepository->countAll();
    }

    public function getCountToday()
    {
        return $this->fileRepository->countToday();
    }

    public function getCountYesterday()
    {
        return $this->fileRepository->countYesterday();
    }

    public function getCountCategory($category)
    {
        return $this->fileRepository->countCategory($category);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'file';
    }
}
