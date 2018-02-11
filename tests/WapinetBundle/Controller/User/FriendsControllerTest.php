<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class FriendsControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/user/friends/list/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
