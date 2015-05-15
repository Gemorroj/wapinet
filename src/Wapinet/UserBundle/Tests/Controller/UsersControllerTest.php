<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UsersControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/users');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
