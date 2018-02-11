<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class EventsControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/user/events');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
