<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Friend.
 *
 * @ORM\Table(name="user_friend")
 * @ORM\Entity(repositoryClass="App\Repository\FriendRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Friend
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private $createdAt;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var \App\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="friends")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    /**
     * @var \App\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="friended")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="friend_id", referencedColumnName="id")
     * })
     */
    private $friend;

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
     * @ORM\PrePersist
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
     * @ORM\PreUpdate
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
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
        return (string) $this->getFriend();
    }
}
