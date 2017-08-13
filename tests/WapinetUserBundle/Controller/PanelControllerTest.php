<?php

namespace Tests\WapinetUserBundle\Controller;

use Tests\WapinetUserBundle\WebTestCaseWapinetUser;

class PanelControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/panel/edit');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
