<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class FileControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/file/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
