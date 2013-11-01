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
}
