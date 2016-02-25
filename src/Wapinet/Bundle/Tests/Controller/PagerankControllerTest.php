<?php

namespace Wapinet\Bundle\Tests\Controller;

use Wapinet\Bundle\Tests\WebTestCaseWapinet;

class PagerankControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/pagerank/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
