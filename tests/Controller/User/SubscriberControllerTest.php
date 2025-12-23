<?php

namespace App\Tests\Controller\User;

use App\Tests\WebTestCaseWapinet;

class SubscriberControllerTest extends WebTestCaseWapinet
{
    public function testIndex(): void
    {
        $client = static::loginAdmin();

        $crawler = $client->request('GET', '/user/subscriber/edit');
        static::assertSame(200, $client->getResponse()->getStatusCode());
    }
}
