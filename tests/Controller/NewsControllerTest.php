<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class NewsControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/news');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
