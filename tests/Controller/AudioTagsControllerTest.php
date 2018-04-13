<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class AudioTagsControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/audio_tags/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
