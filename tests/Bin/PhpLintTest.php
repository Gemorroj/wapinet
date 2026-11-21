<?php

declare(strict_types=1);

namespace App\Tests\Bin;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class PhpLintTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = \sys_get_temp_dir().'/wapinet-php-lint-'.\bin2hex(\random_bytes(8));
        \mkdir($this->directory, 0o700, true);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->directory.'/*') ?: [] as $file) {
            @\unlink($file);
        }

        @\rmdir($this->directory);
    }

    public function testDoesNotExecuteCheckedCode(): void
    {
        $marker = $this->directory.'/executed';
        $file = $this->directory.'/valid.php';
        \file_put_contents($file, '<?php file_put_contents('.\var_export($marker, true).', "executed");');

        $process = new Process([\PHP_BINARY, __DIR__.'/../../bin/php-lint.php', $file]);
        $process->run();

        self::assertSame(0, $process->getExitCode());
        self::assertFileDoesNotExist($marker);
        self::assertStringContainsString('No syntax errors detected', $process->getOutput());
    }

    public function testReportsSyntaxError(): void
    {
        $file = $this->directory.'/invalid.php';
        \file_put_contents($file, '<?php if (');

        $process = new Process([\PHP_BINARY, __DIR__.'/../../bin/php-lint.php', $file]);
        $process->run();

        self::assertSame(255, $process->getExitCode());
        self::assertStringContainsString('Parse error', $process->getOutput());
    }
}
