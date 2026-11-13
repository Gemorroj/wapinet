<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Obfuscator;

use App\Service\Api\Obfuscator\ObfuscatorException;
use App\Service\Api\Obfuscator\ObfuscatorRunner;
use App\Service\Api\Obfuscator\PhpCodeObfuscatorInterface;
use App\Service\PhpValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ObfuscatorRunnerTest extends TestCase
{
    public function testReturnsValidObfuscatedCode(): void
    {
        $runner = $this->runner('<?php echo 1;');

        self::assertSame('<?php echo 1;', $runner->run('<?php echo "Hello";'));
    }

    public function testRejectsInvalidObfuscatedCode(): void
    {
        $runner = $this->runner('<?php if (');

        $this->expectException(ObfuscatorException::class);
        $this->expectExceptionMessage('Результат обфускации не прошёл проверку синтаксиса.');

        $runner->run('<?php echo "Hello";');
    }

    private function runner(string $result): ObfuscatorRunner
    {
        $obfuscator = $this->createStub(PhpCodeObfuscatorInterface::class);
        $obfuscator->method('obfuscate')->willReturn($result);

        $validator = new PhpValidator(\PHP_BINARY, \sys_get_temp_dir());

        return new ObfuscatorRunner($obfuscator, $validator, new NullLogger());
    }
}
