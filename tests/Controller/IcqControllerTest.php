<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class IcqControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/icq/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
