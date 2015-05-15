<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseUser;

class SubscriberControllerTest extends WebTestCaseUser
{
    public function testIndex()
    {
        $client = static::createClientUser('admin');

        $crawler = $client->request('GET', '/subscriber/edit');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
