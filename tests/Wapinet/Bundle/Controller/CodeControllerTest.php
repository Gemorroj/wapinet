<?php

namespace Tests\Wapinet\Bundle\Controller;

use Tests\Wapinet\Bundle\WebTestCaseWapinet;

class CodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/code/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
