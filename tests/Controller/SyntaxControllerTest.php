<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class SyntaxControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/syntax?f=test');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
