<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class EmailControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/email');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
