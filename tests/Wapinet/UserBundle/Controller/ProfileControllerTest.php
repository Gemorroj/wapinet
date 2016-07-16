<?php

namespace Tests\Wapinet\UserBundle\Controller;

use Tests\Wapinet\UserBundle\WebTestCaseWapinetUser;

class ProfileControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/profile/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
