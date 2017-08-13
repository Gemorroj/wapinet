<?php

namespace Tests\WapinetBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class RenameControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/rename/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
