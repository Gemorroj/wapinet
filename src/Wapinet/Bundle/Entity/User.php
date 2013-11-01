<?php
// TODO: http://symfony.com/doc/current/cookbook/doctrine/file_uploads.html
// http://symfonydev.ru/file-upload-symfony-2/
// переписать
namespace Wapinet\Bundle\Entity;

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
    protected $avatar;
    /**
     * @var File|null
     */
    protected $avatarFile;


    public function __construct()
    {
        parent::__construct();
        // your own logic
    }

    /**
     * @return File|null
     */
    public function getAvatarFile()
    {
        return $this->avatarFile;
    }

    /**
     * @param File $avatarFile
     */
    public function setAvatarFile(File $avatarFile = null)
    {
        $this->avatarFile = $avatarFile;
        $this->setAvatar($avatarFile->getRealPath());
    }

    /**
     * @return string|null
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     */
    public function setAvatar($avatar = null)
    {
        $this->avatar = $avatar;
    }
}
