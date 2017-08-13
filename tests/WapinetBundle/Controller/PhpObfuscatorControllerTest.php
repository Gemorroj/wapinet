<?php

namespace Tests\WapinetBundle\Controller;

use Tests\WapinetBundle\WebTestCaseWapinet;

class PhpObfuscatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/php_obfuscator/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
