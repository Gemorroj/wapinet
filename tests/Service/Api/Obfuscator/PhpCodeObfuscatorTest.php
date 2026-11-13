<?php

declare(strict_types=1);

namespace App\Tests\Service\Api\Obfuscator;

use App\Service\Api\Obfuscator\ObfuscatorException;
use App\Service\Api\Obfuscator\ObfuscatorLevel;
use App\Service\Api\Obfuscator\PhpCodeObfuscator;
use PHPUnit\Framework\TestCase;

final class PhpCodeObfuscatorTest extends TestCase
{
    public function testObfuscatesCodeAndHidesStringLiterals(): void
    {
        $result = new PhpCodeObfuscator()->obfuscate('<?php echo "Hello World";');

        self::assertStringStartsWith('<?php', $result);
        self::assertStringNotContainsString('Hello World', $result);
        self::assertMatchesRegularExpression('/base64_decode|str_rot13|strrev|chr\(/', $result);
    }

    public function testLightAndNormalLevelsDiffer(): void
    {
        $code = '<?php echo "Hello";';

        $light = new PhpCodeObfuscator()->obfuscate($code, true, ObfuscatorLevel::Light);
        $normal = new PhpCodeObfuscator()->obfuscate($code, true, ObfuscatorLevel::Normal);

        self::assertStringContainsString('Hello', $light);
        self::assertStringNotContainsString('Hello', $normal);
    }

    public function testAddsOpeningTagWhenMissing(): void
    {        $result = new PhpCodeObfuscator()->obfuscate('echo 1 + 1;');

        self::assertStringStartsWith('<?php', $result);
    }

    public function testKeepsCommentsOnlyWhenRequested(): void
    {
        $code = "<?php\n// keep me\necho 1;";

        $withComments = new PhpCodeObfuscator()->obfuscate($code, false);
        $withoutComments = new PhpCodeObfuscator()->obfuscate($code, true);

        self::assertStringContainsString('keep me', $withComments);
        self::assertStringNotContainsString('keep me', $withoutComments);
    }

    public function testRejectsInvalidPhp(): void
    {
        $this->expectException(ObfuscatorException::class);

        new PhpCodeObfuscator()->obfuscate('<?php if (');
    }
}


