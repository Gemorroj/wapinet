<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class FriendsControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/friends/list/admin');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
