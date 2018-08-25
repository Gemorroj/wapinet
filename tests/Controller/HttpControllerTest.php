<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class HttpControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/http/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
