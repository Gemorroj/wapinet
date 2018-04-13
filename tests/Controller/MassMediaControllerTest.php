<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class MassMediaControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/mass_media/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
