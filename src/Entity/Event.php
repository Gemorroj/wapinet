<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Event.
 *
 * @ORM\Table(name="event")
 * @ORM\Entity(repositoryClass="App\Repository\EventRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Event
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
     * @var string
     *
     * @ORM\Column(name="subject", type="string", nullable=false)
     */
    private $subject;

    /**
     * @var array|null
     *
     * @ORM\Column(name="variables", type="array", nullable=true)
     */
    private $variables;

    /**
     * @var string
     *
     * @ORM\Column(name="template", type="string")
     */
    private $template;

    /**
     * @var bool
     *
     * @ORM\Column(name="need_email", type="boolean", nullable=false)
     */
    private $needEmail = false;

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
     * @param int $id
     *
     * @return Event
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

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
     * @param string $subject
     *
     * @return Event
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

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
     * @param string $template
     *
     * @return Event
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param array $variables
     *
     * @return Event
     */
    public function setVariables(array $variables = null)
    {
        $this->variables = $variables;

        return $this;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @return Event
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
     * @param bool $needEmail
     *
     * @return Event
     */
    public function setNeedEmail($needEmail)
    {
        $this->needEmail = $needEmail;

        return $this;
    }

    /**
     * @return bool
     */
    public function getNeedEmail()
    {
        return $this->needEmail;
    }
}
