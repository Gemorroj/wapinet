<?php

namespace Tests\Wapinet\UserBundle\Controller;

use Tests\Wapinet\UserBundle\WebTestCaseWapinetUser;

class EventsControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/events');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
