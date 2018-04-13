<?php

namespace Tests\WapinetUserBundle\Controller;

use App\Tests\WebTestCaseWapinet;

class FriendsControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/friends/list/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
