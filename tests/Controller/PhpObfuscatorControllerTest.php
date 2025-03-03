<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class PhpObfuscatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/php_obfuscator');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
