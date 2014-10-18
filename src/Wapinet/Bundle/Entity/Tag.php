<?php

namespace Wapinet\Bundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * Tag
 */
class Tag
{
    /**
     * @var integer
     */
    private $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var ArrayCollection
     */
    protected $files;
    /**
     * @var int
     */
    protected $count = 0;
    /**
     * @var int
     */
    protected $countPassword = 0;
    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;


    /**
     * Констркутор
     */
    public function __construct()
    {
        $this->files = new ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return Tag
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set count
     *
     * @param int $count
     * @return Tag
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count password
     *
     * @return integer
     */
    public function getCountPassword()
    {
        return $this->countPassword;
    }

    /**
     * Set count password
     *
     * @param int $countPassword
     * @return Tag
     */
    public function setCountPassword($countPassword)
    {
        $this->countPassword = $countPassword;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Tag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get file
     *
     * @return ArrayCollection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Set file
     *
     * @param ArrayCollection $files
     * @return Tag
     */
    public function setFiles(ArrayCollection $files)
    {
        $this->files = $files;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return File
     */
    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return File
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
