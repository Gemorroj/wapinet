<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class FriendsControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/friends/list/admin');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

}
