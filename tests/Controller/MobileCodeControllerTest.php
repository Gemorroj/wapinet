<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class MobileCodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/mobile_code');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
