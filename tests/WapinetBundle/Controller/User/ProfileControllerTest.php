<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class ProfileControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/profile/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
