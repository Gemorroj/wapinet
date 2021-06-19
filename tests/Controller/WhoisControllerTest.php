<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class WhoisControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/whois');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
