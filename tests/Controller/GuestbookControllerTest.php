<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class GuestbookControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/guestbook');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
