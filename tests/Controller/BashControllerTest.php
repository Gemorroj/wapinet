<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class BashControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/bash/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}