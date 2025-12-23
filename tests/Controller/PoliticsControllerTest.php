<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class PoliticsControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/politics');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
