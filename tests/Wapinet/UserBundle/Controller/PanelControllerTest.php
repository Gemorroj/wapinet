<?php

namespace Tests\Wapinet\UserBundle\Controller;

use Tests\Wapinet\UserBundle\WebTestCaseWapinetUser;

class PanelControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/panel/edit');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
