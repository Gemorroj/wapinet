<?php
namespace Wapinet\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Iphp\FileStoreBundle\Mapping\Annotation as FileStore;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use FOS\MessageBundle\Model\ParticipantInterface;

/**
 * User
 * @FileStore\Uploadable
 */
class User extends BaseUser implements ParticipantInterface
{
    const SEX_M = 'm';
    const SEX_F = 'f';

    /**
     * @var array|UploadedFile|null $avatar
     * @FileStore\UploadableField(mapping="avatar")
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
     * @var string
     */
    protected $sex;

    /**
     * @var \DateTime|null
     */
    protected $birthday;



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
     * @return string
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
        return (null !== $this->avatar);
    }
}
