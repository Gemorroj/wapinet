<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseUser;

class FriendsControllerTest extends WebTestCaseUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/friends/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
