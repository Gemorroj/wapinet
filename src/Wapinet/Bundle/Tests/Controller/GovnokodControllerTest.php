<?php

namespace Wapinet\Bundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GovnokodControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/govnokod/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
