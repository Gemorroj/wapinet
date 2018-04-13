<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class TranslateControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/translate/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
