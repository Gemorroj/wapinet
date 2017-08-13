<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetUserBundle\WebTestCaseWapinetUser;

class ProfileControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/profile/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
