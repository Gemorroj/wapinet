<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class AudioTagsControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/audio_tags');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
