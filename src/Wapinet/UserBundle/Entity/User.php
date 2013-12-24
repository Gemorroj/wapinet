<?php
namespace Wapinet\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use FOS\MessageBundle\Model\ParticipantInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 * @Vich\Uploadable
 */
class User extends BaseUser implements ParticipantInterface
{
    const LIFETIME = '5 minutes';
    const SEX_M = 'm';
    const SEX_F = 'f';

    /**
     * @Assert\Image(
     *     maxSize="100k",
     *     minWidth=13,
     *     maxWidth=500,
     *     minHeight=13,
     *     maxHeight=500,
     *     mimeTypes={"image/png", "image/jpeg", "image/gif"},
     *     groups={"Profile"}
     * )
     * @Vich\UploadableField(mapping="user_avatar", fileNameProperty="avatarName")
     *
     * @var File|null
     */
    protected $avatar;

    /**
     * @var string|null
     */
    protected $avatarName;

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
    protected $lastActivity;

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

    /**
     * @var bool
     */
    protected $subscribeNews = true;

    /**
     * @var bool
     */
    protected $subscribeFriends = false;

    /**
     * @var Panel
     */
    protected $panel;

    /**
     * @var ArrayCollection
     */
    protected $friends;

    /**
     * @var ArrayCollection
     */
    protected $friended;

    /**
     * @var string
     */
    protected $info;


    public function __construct()
    {
        parent::__construct();
        $this->friends = new ArrayCollection();
        $this->friended = new ArrayCollection();
    }


    /**
     * @return ArrayCollection
     */
    public function getFriends()
    {
        return $this->friends;
    }

    /**
     * @return ArrayCollection
     */
    public function getFriended()
    {
        return $this->friended;
    }


    /**
     * @return bool
     */
    public function isOnline()
    {
        $lastActivity = $this->getLastActivity();
        if (null !== $lastActivity) {
            if ($lastActivity > new \DateTime('now -' . self::LIFETIME)) {
                return true;
            }
        }

        return false;
    }


    /**
     * @param Panel $panel
     * @return User
     */
    public function setPanel(Panel $panel)
    {
        $this->panel = $panel;

        return $this;
    }

    /**
     * @return Panel
     */
    public function getPanel()
    {
        if (null === $this->panel) {
            $panel = new Panel();
            $panel->setUser($this);

            return $panel;
        }

        return $this->panel;
    }


    /**
     * @return string|null
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $info
     * @return User
     */
    public function setInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSubscribeFriends()
    {
        return $this->subscribeFriends;
    }

    /**
     * @param bool $subscribeFriends
     * @return User
     */
    public function setSubscribeFriends($subscribeFriends)
    {
        $this->subscribeFriends = (bool)$subscribeFriends;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSubscribeNews()
    {
        return $this->subscribeNews;
    }

    /**
     * @param bool $subscribeNews
     * @return User
     */
    public function setSubscribeNews($subscribeNews)
    {
        $this->subscribeNews = (bool)$subscribeNews;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSubscribeComments()
    {
        return $this->subscribeComments;
    }

    /**
     * @param bool $subscribeComments
     * @return User
     */
    public function setSubscribeComments($subscribeComments)
    {
        $this->subscribeComments = (bool)$subscribeComments;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSubscribeMessages()
    {
        return $this->subscribeMessages;
    }

    /**
     * @param bool $subscribeMessages
     * @return User
     */
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


    /**
     * @return null|File
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param File|null $avatar
     *
     * @return User
     */
    public function setAvatar($avatar = null)
    {
        $tmp = $this->avatar;

        $this->avatar = $avatar;

        if ($this->avatar !== $tmp) {
            $this->setUpdatedAtValue();
        }

        return $this;
    }


    /**
     * @return null|string
     */
    public function getAvatarName()
    {
        return $this->avatarName;
    }

    /**
     * @param string|null $avatarName
     *
     * @return User
     */
    public function setAvatarName($avatarName)
    {
        $this->avatarName = $avatarName;

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
     * @return User
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
     * @return User
     */
    public function setUpdatedAtValue()
    {
        $this->updatedAt = new \DateTime();

        return $this;
    }

    /**
     * @param \DateTime $lastActivity
     *
     * @return User
     */
    public function setLastActivity(\DateTime $lastActivity)
    {
        $this->lastActivity = $lastActivity;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }


    /**
     * return bool
     */
    public function hasAvatar()
    {
        if ($this->avatar instanceof UploadedFile) {
            return false;
        }

        return (null !== $this->avatar);
    }

    /**
     * @return array
     */
    public static function getSexChoices()
    {
        return array(User::SEX_M => 'Мужской', User::SEX_F => 'Женский');
    }
}
