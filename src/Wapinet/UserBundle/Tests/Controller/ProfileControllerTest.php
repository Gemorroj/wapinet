<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseUser;

class ProfileControllerTest extends WebTestCaseUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/profile/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
