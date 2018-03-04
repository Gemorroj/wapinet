<?php

namespace Tests\WapinetBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class HttpControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/http/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
