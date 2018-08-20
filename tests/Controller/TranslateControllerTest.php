<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class TranslateControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/translate/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
