<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class CodeControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/code/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}