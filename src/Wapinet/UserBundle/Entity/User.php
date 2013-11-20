<?php
namespace Wapinet\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use FOS\MessageBundle\Model\ParticipantInterface;
use Wapinet\Bundle\Entity\File;

/**
 * User
 */
class User extends BaseUser implements ParticipantInterface
{
    const SEX_M = 'm';
    const SEX_F = 'f';

    /**
     * @var File|null $avatar
     */
    protected $avatar;

    /**
     * @var \DateTime
     */
    protected $createdAt;
    /**
     * @var \DateTime|null
     */
    protected $updatedAt;

    /**
     * @var string|null
     */
    protected $sex;

    /**
     * @var \DateTime|null
     */
    protected $birthday;

    /**
     * @var bool
     */
    protected $subscribeComments = true;

    /**
     * @var bool
     */
    protected $subscribeMessages = true;



    public function getSubscribeComments()
    {
        return $this->subscribeComments;
    }

    public function setSubscribeComments($subscribeComments)
    {
        $this->subscribeComments = (bool)$subscribeComments;
        return $this;
    }

    public function getSubscribeMessages()
    {
        return $this->subscribeMessages;
    }

    public function setSubscribeMessages($subscribeMessages)
    {
        $this->subscribeMessages = (bool)$subscribeMessages;
        return $this;
    }


    /**
     * @param \DateTime $birthday
     * @return User
     */
    public function setBirthday(\DateTime $birthday = null)
    {
        $this->birthday = $birthday;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * @param string $sex
     * @return User
     * @throws \InvalidArgumentException
     */
    public function setSex($sex)
    {
        if ($sex !== self::SEX_M && $sex !== self::SEX_F) {
            throw new \InvalidArgumentException('Invalid sex');
        }

        $this->sex = $sex;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSex()
    {
        return $this->sex;
    }


    public function getAvatar()
    {
        return $this->avatar;
    }

    public function setAvatar($avatar = null)
    {
        $this->avatar = $avatar;
        return $this;
    }


    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAtValue()
    {
        $this->createdAt = new \DateTime();
        return $this;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();
        return $this;
    }

    /**
     * return bool
     */
    public function hasAvatar()
    {
        return (null !== $this->avatar && true === $this->avatar->hasFile());
    }

    /**
     * @return array
     */
    public static function getSexChoices()
    {
        return array(User::SEX_M => 'Мужской', User::SEX_F => 'Женский');
    }
}
