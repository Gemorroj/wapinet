<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class HashControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hash');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
