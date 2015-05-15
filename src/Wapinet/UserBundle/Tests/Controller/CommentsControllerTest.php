<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentsControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/comments/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
