<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class EmailControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/email/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}