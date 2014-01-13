<?php

namespace Wapinet\Bundle\Entity;

use Wapinet\UserBundle\Entity\User;


/**
 * Govnokod
 */
class Govnokod
{
    /**
     * @var integer
     */
    private $id;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var string
     */
    protected $ip;
    /**
     * @var string
     */
    protected $browser;
    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;
    /**
     * @var string
     */
    protected $body;
    /**
     * @var string
     */
    protected $subject;

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
     * @return Govnokod
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return Govnokod
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     * @return Govnokod
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * @param string $browser
     * @return Govnokod
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;

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
     * @return Govnokod
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
     * @return Govnokod
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return Govnokod
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }


    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     * @return Govnokod
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }
}
