<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetUserBundle\WebTestCaseWapinetUser;

class SubscriberControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/subscriber/edit');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
