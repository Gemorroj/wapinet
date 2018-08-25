<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class UsersControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/user/users');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

}
