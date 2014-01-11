<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseUser;

class ProfileControllerTest extends WebTestCaseUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/profile/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
