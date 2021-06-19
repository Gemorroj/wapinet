<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class RatesControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/rates');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
