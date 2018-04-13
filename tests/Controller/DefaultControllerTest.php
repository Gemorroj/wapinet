<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class DefaultControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
