<?php

namespace Wapinet\UserBundle\Tests\Controller;

use Wapinet\UserBundle\Tests\WebTestCaseWapinetUser;

class CommentsControllerTest extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/comments/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
