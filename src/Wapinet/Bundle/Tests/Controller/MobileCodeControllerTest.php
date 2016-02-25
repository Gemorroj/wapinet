<?php

namespace Wapinet\Bundle\Tests\Controller;

use Wapinet\Bundle\Tests\WebTestCaseWapinet;

class MobileCodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/mobile_code/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
