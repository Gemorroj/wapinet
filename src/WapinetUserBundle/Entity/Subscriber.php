<?php
namespace WapinetUserBundle\Entity;

/**
 * Subscriber
 */
class Subscriber extends \ArrayObject
{
    protected $id;

    /**
     * @var bool
     */
    protected $emailMessages = true;

    /**
     * @var bool
     */
    protected $emailNews = true;

    /**
     * @var bool
     */
    protected $emailFriends = false;

    /**
     * @var User
     */
    protected $user;

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
     * @param User $user
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
     * @return Subscriber
     */
    public function setEmailFriends($emailFriends)
    {
        $this->emailFriends = (bool)$emailFriends;

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
     * @return Subscriber
     */
    public function setEmailNews($emailNews)
    {
        $this->emailNews = (bool)$emailNews;

        return $this;
    }

    /**
     * @return bool
     */
    public function getEmailMessages()
    {
        return $this->emailMessages;
    }

    /**
     * @param bool $emailMessages
     * @return Subscriber
     */
    public function setEmailMessages($emailMessages)
    {
        $this->emailMessages = (bool)$emailMessages;

        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator(array(
            'emailMessages' => array(
                'name' => 'Сообщения',
                'enabled' => $this->getEmailMessages(),
            ),
            'emailNews' => array(
                'name' => 'Новости',
                'enabled' => $this->getEmailNews(),
            ),
            'emailFriends =' => array(
                'name' => 'События друзей',
                'enabled' => $this->getEmailFriends(),
            ),
        ));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $result = '';
        foreach ($this->getIterator() as $item) {
            if (true === $item['enabled']) {
                $result .= $item['name'] . ', ';
            }
        }

        return \rtrim($result, ', ');
    }
}