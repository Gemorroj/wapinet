<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class NewsControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/news/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
