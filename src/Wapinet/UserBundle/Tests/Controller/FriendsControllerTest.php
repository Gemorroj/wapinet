<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseWapinetUser;

class FriendsControllerTestWapinet extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/friends/list/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
