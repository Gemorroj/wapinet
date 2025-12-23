<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class ProfileControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/profile');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }

    public function testEdit(): void
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/edit');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
