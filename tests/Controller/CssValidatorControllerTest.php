<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class CssValidatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/css_validator');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
