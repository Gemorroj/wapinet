<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebTestCaseWapinet extends WebTestCase
{
    /**
     * @return string
     */
    protected function getFixturesPath(): string
    {
        return __DIR__.'/fixtures';
    }

    /**
     * @return Client
     */
    protected static function loginAdmin(): Client
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);
    }
}
