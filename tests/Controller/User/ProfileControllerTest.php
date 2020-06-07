<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class ProfileControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/profile');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEdit()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/edit');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
