<?php

namespace Tests\WapinetUserBundle\Controller;

use App\Tests\WebTestCaseWapinet;

class PanelControllerTestWapinet extends WebTestCaseWapinet
{
    public function testIndex()
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/panel/edit');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }

}
