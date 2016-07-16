<?php

namespace Tests\Wapinet\UserBundle\Controller;

use Tests\Wapinet\UserBundle\WebTestCaseWapinetUser;

class UsersControllerTest extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/users');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
