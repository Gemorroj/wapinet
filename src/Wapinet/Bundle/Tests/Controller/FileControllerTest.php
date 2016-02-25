<?php

namespace Wapinet\Bundle\Tests\Controller;

use Wapinet\Bundle\Tests\WebTestCaseWapinet;

class FileControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/file/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
