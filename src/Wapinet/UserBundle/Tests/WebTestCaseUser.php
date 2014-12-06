<?php
namespace Wapinet\UserBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Bundle\FrameworkBundle\Client;

class WebTestCaseUser extends WebTestCase
{
    /**
     * @param string $username
     * @param array  $options
     * @param array  $server
     *
     * @return Client
     */
    protected function createClientUser($username, array $options = array(), array $server = array())
    {
        $client = static::createClient($options, $server);
        $container = $client->getContainer();

        // авторизация
        $session = $container->get('session');
        /** @var $userManager \FOS\UserBundle\Doctrine\UserManager */
        $userManager = $container->get('fos_user.user_manager');
        /** @var $loginManager \FOS\UserBundle\Security\LoginManager */
        $loginManager = $container->get('fos_user.security.login_manager');
        $firewallName = $container->getParameter('fos_user.firewall_name');

        $user = $userManager->findUserByUsername($username);
        $loginManager->loginUser($firewallName, $user);

        // save the login token into the session and put it in a cookie
        $container->get('session')->set('_security_' . $firewallName, serialize($container->get('security.token_storage')->getToken()));
        $container->get('session')->save();
        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        return $client;
    }
}
