<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class HashControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hash/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
