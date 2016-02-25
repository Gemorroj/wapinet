<?php

namespace Wapinet\Bundle\Tests\Controller;

use Wapinet\Bundle\Tests\WebTestCaseWapinet;

class CodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/code/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
