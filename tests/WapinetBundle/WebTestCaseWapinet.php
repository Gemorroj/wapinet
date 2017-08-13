<?php
namespace Tests\WapinetBundle;

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
}
