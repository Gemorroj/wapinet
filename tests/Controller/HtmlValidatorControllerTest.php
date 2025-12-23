<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class HtmlValidatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/html_validator');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
