<?php
namespace Wapinet\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Iphp\FileStoreBundle\Mapping\Annotation as FileStore;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * User
 * @FileStore\Uploadable
 */
class User extends BaseUser
{
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
