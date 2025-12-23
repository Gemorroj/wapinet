<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class BrowserInfoControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/browser_info');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
