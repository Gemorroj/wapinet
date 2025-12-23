<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class CodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/code');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
