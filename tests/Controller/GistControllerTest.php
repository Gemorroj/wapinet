<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class GistControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/gist');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
