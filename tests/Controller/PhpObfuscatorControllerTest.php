<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class PhpObfuscatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/php_obfuscator/');
        self::assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
