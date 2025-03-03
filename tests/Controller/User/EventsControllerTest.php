<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class EventsControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/events');
        self::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
