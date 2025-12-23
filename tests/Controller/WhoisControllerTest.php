<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class WhoisControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/whois');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
