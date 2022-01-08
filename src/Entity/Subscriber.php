<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_subscriber")
 * @ORM\Entity(repositoryClass="App\Repository\SubscriberRepository")
 */
class Subscriber extends \ArrayObject
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer", nullable=false)
     */
    private $id;

    /**
     * @ORM\Column(name="email_news", type="boolean", nullable=false)
     */
    private bool $emailNews = true;

    /**
     * @ORM\Column(name="email_friends", type="boolean", nullable=false)
     */
    private bool $emailFriends = true;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="subscriber", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true, nullable=false)
     * })
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getEmailFriends(): bool
    {
        return $this->emailFriends;
    }

    public function setEmailFriends(bool $emailFriends): self
    {
        $this->emailFriends = $emailFriends;

        return $this;
    }

    public function getEmailNews(): bool
    {
        return $this->emailNews;
    }

    public function setEmailNews(bool $emailNews): self
    {
        $this->emailNews = $emailNews;

        return $this;
    }

    /**
     * @return \ArrayIterator<string, array<string, string>>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator([
            'emailNews' => [
                'name' => 'Новости',
                'enabled' => $this->getEmailNews(),
            ],
            'emailFriends =' => [
                'name' => 'События друзей',
                'enabled' => $this->getEmailFriends(),
            ],
        ]);
    }

    public function __toString(): string
    {
        $result = '';
        foreach ($this->getIterator() as $item) {
            if (true === $item['enabled']) {
                $result .= $item['name'].', ';
            }
        }

        return \rtrim($result, ', ');
    }
}
