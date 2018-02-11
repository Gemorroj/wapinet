<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class SubscriberControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/user/subscriber/edit');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
