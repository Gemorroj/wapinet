<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class UnicodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/unicode');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
