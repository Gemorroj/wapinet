<?php

declare(strict_types=1);

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class WebTestCaseWapinet extends WebTestCase
{
    protected function getFixturesPath(): string
    {
        return __DIR__.'/fixtures';
    }
}
