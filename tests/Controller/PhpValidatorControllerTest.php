<?php

namespace App\Tests\Controller;

use App\Tests\WebTestCaseWapinet;

class PhpValidatorControllerTest extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/php_validator/');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
