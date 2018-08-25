<?php

namespace App\Entity;


/**
 * Tag
 */
class Tag
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    protected $name;
    /**
     * @var int
     */
    protected $count = 0;
    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;


    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get count
     *
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set count
     *
     * @param int $count
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = $count;

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
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * @return $this
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
     * @return $this
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
