<?php
namespace Wapinet\UserBundle\Entity;

/**
 * Friend
 */
class Friend
{
    /**
     * @var int
     */
    protected $id;
    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var User
     */
    protected $friend;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $friend
     *
     * @return $this
     */
    public function setFriend(User $friend)
    {
        $this->friend = $friend;

        return $this;
    }

    /**
     * @return User
     */
    public function getFriend()
    {
        return $this->friend;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getFriend();
    }
}
