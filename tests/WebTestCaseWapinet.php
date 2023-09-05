<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebTestCaseWapinet extends WebTestCase
{
    protected function getFixturesPath(): string
    {
        return __DIR__.'/fixtures';
    }

    protected static function loginAdmin(): KernelBrowser
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => $_ENV['APP_USER'],
            'PHP_AUTH_PW' => $_ENV['APP_PASSWD'],
        ]);
    }
}
