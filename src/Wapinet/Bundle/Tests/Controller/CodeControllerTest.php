<?php

namespace Wapinet\Bundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CodeControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/code/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}