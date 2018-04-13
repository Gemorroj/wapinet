<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class BrowserInfoControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/browser_info');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
