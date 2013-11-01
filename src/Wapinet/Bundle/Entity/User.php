<?php
namespace Wapinet\Bundle\Entity;

use Wapinet\UserBundle\Entity\User as BaseUser;


/**
 * User
 */
class User extends BaseUser
{
    public function __construct()
    {
        parent::__construct();
        // your own logic
    }
}
