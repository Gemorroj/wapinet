<?php

namespace Wapinet\Bundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BrowserInfoControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/browser_info');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

}
