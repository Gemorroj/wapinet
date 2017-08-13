<?php

namespace Tests\WapinetBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class HtmlValidatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/html_validator/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
