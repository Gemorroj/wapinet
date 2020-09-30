<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Gist.
 *
 * @ORM\Table(name="gist")
 * @ORM\Entity(repositoryClass="App\Repository\GistRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Gist
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", nullable=false)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="browser", type="string", nullable=false)
     */
    private $browser;

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
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=false)
     * @Assert\Length(max=4294967295, allowEmptyString="false")
     */
    private $body;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=5000, nullable=false)
     * @Assert\Length(max=5000, allowEmptyString="false")
     */
    private $subject;

    /**
     * @var \App\Entity\User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $user;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Gist
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set user.
     *
     * @return Gist
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
     *
     * @return Gist
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
     *
     * @return Gist
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
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return Gist
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
     *
     * @return Gist
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }
}
