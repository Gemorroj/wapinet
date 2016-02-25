<?php

namespace Wapinet\Bundle\Tests\Controller;

use Wapinet\Bundle\Tests\WebTestCaseWapinet;

class GistControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/gist/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
