<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class SyntaxControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/syntax?f=test');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
