<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class RatesControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/rates');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
