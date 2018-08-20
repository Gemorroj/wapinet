<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class RenameControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/rename/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
