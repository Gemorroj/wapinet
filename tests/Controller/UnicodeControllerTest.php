<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class UnicodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/unicode/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
