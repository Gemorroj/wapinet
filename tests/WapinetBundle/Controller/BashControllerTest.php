<?php

namespace Tests\WapinetBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class BashControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/bash/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
