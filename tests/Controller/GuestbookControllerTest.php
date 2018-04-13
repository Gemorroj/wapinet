<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class GuestbookControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/guestbook/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
