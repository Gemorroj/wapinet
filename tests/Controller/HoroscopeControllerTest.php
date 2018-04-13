<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class HoroscopeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/horoscope/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
