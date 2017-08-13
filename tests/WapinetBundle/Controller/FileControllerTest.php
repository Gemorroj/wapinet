<?php

namespace Tests\WapinetBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class FileControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/file/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
