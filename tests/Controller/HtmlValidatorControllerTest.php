<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class HtmlValidatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/html_validator/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
