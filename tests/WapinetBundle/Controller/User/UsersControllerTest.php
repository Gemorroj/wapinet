<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class UsersControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/user/users');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
