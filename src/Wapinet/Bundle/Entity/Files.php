<?php

namespace Wapinet\Bundle\Entity;

use Wapinet\UserBundle\Entity\User;

/**
 * Files
 */
class Files
{
    /**
     * @var integer
     */
    private $id;
    /**
     * @var User|null
     */
    protected $user;
    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;
    /**
     * @var \DateTime|null
     */
    protected $lastDownloadAt;
    /**
     * @var int
     */
    protected $countDownloads;

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
     * @return Files
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get user
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Files
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

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
     * @return Files
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
     * @return Files
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastDownloadAt()
    {
        return $this->lastDownloadAt;
    }

    /**
     * @param \DateTime $lastDownloadAt
     * @return Files
     */
    public function setLastDownloadAt(\DateTime $lastDownloadAt)
    {
        $this->lastDownloadAt = $lastDownloadAt;

        return $this;
    }

    /**
     * @return int
     */
    public function getCountDownloads()
    {
        return $this->countDownloads;
    }

    /**
     * @param int $countDownloads
     * @return Files
     */
    public function setCountDownloads($countDownloads)
    {
        $this->countDownloads = $countDownloads;

        return $this;
    }
}
