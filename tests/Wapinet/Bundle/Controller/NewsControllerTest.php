<?php

namespace Tests\Wapinet\Bundle\Controller;

use Tests\Wapinet\Bundle\WebTestCaseWapinet;

class NewsControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/news/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
