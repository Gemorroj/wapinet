<?php
namespace Wapinet\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Component\HttpFoundation\File\File;


/**
 * User
 */
class User extends BaseUser
{
    /**
     * @var string|null
     */
    protected $avatarName;
    /**
     * @var File|null $avatar
     */
    protected $avatar;


    public function __construct()
    {
        parent::__construct();
        // your own logic
    }


    public function getAvatar()
    {
        return $this->avatar;
    }

    public function getAvatarName()
    {
        return $this->avatarName;
    }

    public function setAvatar(File $avatar = null)
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function setAvatarName($avatarName = null)
    {
        $this->avatarName = $avatarName;
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
