<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class WhoisControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/whois/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
