<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class PanelControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/panel/edit');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
