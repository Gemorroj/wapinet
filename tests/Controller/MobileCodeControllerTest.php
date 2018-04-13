<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class MobileCodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/mobile_code/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
