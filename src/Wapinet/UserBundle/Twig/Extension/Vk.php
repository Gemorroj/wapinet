<?php

namespace Wapinet\UserBundle\Twig\Extension;

use Wapinet\Bundle\Helper\Curl;
use Wapinet\UserBundle\Entity\User;

class Vk extends \Twig_Extension
{
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @param Curl $curl
     */
    public function __construct(Curl $curl)
    {
        $this->curl = $curl;
    }

    /**
     * Returns a list of global functions to add to the existing list.
     *
     * @return array An array of global functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('wapinet_user_get_vk_info', array($this, 'getInfo')),
        );
    }


    /**
     * @param User $user
     * @return array|null
     */
    public function getInfo(User $user)
    {
        try {
            $response = $this->curl->init(
                'https://api.vk.com/method/users.get?fields=online&user_ids=' . $user->getVk()
            )->addCompression()->exec()->getContent();
            $response = \json_decode($response);
        } catch (\Exception $e) {
            $response = null;
        }

        return $response;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'wapinet_user_vk';
    }
}
