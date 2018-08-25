<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class ProfileControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/profile/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

}
