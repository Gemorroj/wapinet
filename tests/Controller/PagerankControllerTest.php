<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class PagerankControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/pagerank/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
