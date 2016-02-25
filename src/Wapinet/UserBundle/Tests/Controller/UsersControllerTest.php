<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseWapinetUser;

class UsersControllerTest extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/users');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
