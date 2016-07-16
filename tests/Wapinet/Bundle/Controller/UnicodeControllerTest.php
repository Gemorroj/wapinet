<?php

namespace Tests\Wapinet\Bundle\Controller;

use Tests\Wapinet\Bundle\WebTestCaseWapinet;

class UnicodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/unicode/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
