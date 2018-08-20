<?php

namespace Tests\WapinetUserBundle\Controller;

use App\Tests\WebTestCaseWapinet;

class EventsControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/events');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

}
