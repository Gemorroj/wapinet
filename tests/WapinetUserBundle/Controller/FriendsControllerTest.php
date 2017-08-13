<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetUserBundle\WebTestCaseWapinetUser;

class FriendsControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/friends/list/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
