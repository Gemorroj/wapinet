<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class CssValidatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/css_validator/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
