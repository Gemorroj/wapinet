<?php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebTestCaseWapinet extends WebTestCase
{
    /**
     * @return string
     */
    protected function getFixturesPath()
    {
        return \dirname(static::$kernel->getRootDir()).'/tests/fixtures';
    }

    /**
     * @return Client
     */
    protected function loginAdmin()
    {
        return static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'admin',
        ]);
    }
}
