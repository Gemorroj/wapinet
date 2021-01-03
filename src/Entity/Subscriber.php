<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Subscriber.
 *
 * @ORM\Table(name="user_subscriber")
 * @ORM\Entity
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
     * @var bool
     *
     * @ORM\Column(name="email_news", type="boolean", nullable=false)
     */
    private $emailNews = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="email_friends", type="boolean", nullable=false)
     */
    private $emailFriends = true;

    /**
     * @var User
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="subscriber", cascade={"persist"})
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id", unique=true, nullable=false)
     * })
     */
    private $user;

    /**
     * @return int
     */
    public function getId()
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

    /**
     * @return Subscriber
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEmailFriends()
    {
        return $this->emailFriends;
    }

    /**
     * @param bool $emailFriends
     *
     * @return Subscriber
     */
    public function setEmailFriends($emailFriends)
    {
        $this->emailFriends = (bool) $emailFriends;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEmailNews()
    {
        return $this->emailNews;
    }

    /**
     * @param bool $emailNews
     *
     * @return Subscriber
     */
    public function setEmailNews($emailNews)
    {
        $this->emailNews = (bool) $emailNews;

        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
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

    /**
     * @return string
     */
    public function __toString()
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
