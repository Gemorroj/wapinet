<?php

namespace Tests\Wapinet\UserBundle\Controller;

use Tests\Wapinet\UserBundle\WebTestCaseWapinetUser;

class CommentsControllerTest extends WebTestCaseWapinetUser
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/comments/admin');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
