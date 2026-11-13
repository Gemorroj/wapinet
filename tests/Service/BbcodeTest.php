<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Bbcode;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;

final class BbcodeTest extends TestCase
{
    public function testRendersBbcodeAndSpoiler(): void
    {
        $context = new RequestContext();
        $context->setHost('wapinet.ru');

        $html = new Bbcode($context)->render('[b]жирный[/b] [spoiler]секрет[/spoiler]');

        static::assertStringContainsString('<strong', $html);
        static::assertStringContainsString('жирный', $html);
        static::assertStringContainsString('<details', $html);
        static::assertStringContainsString('bb_spoiler_show', $html);
        static::assertStringContainsString('<span class="bb_spoiler_hide" hidden>', $html);
        static::assertStringContainsString('секрет', $html);
    }

    public function testRendersHideAsDetails(): void
    {
        $context = new RequestContext();
        $context->setHost('wapinet.ru');

        $html = new Bbcode($context)->render('[hide]секрет[/hide]');

        static::assertStringContainsString('<details', $html);
        static::assertStringContainsString('секрет', $html);
        static::assertStringNotContainsString('<input', $html);
        static::assertStringNotContainsString('onclick=', $html);
    }

    public function testRendersCodeForClientHighlighter(): void
    {
        $context = new RequestContext();
        $context->setHost('wapinet.ru');

        $html = new Bbcode($context)->render('[php]echo 1;[/php]');

        static::assertStringContainsString('<pre><code class="language-php">', $html);
        static::assertStringNotContainsString('<span style=', $html);
    }
}
