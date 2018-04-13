<?php

namespace Tests\WapinetUserBundle\Controller;

use App\Tests\WebTestCaseWapinet;

class SubscriberControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/subscriber/edit');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
