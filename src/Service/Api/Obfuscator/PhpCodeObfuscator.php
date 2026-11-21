<?php

declare(strict_types=1);

namespace App\Service\Api\Obfuscator;

use PhpObfuscator\Module\CommentStrip;
use PhpObfuscator\Module\IntegerEncoder;
use PhpObfuscator\Module\SilentCalls;
use PhpObfuscator\Module\StringEncoder;
use PhpObfuscator\Module\VarOptimizer;
use PhpObfuscator\PhpObfuscator;

/**
 * Обёртка над gemorroj/php-obfuscator: AST-обфускация вместо прежнего eval-пакета.
 *
 * Обфускация затрудняет чтение кода, но не заменяет расширения вроде ionCube.
 */
final readonly class PhpCodeObfuscator implements PhpCodeObfuscatorInterface
{
    public function obfuscate(
        string $code,
        bool $removeComments = true,
        ObfuscatorLevel $level = ObfuscatorLevel::Max,
    ): string {
        $obfuscator = new PhpObfuscator();

        if (ObfuscatorLevel::Max === $level) {
            $obfuscator->addModule(new VarOptimizer());
            $obfuscator->addModule(new SilentCalls());
        }

        if (ObfuscatorLevel::Light !== $level) {
            $obfuscator->addModule(new StringEncoder());
        }

        $obfuscator->addModule(new IntegerEncoder());

        if ($removeComments) {
            $obfuscator->addModule(new CommentStrip());
        }

        try {
            return $obfuscator->obfuscate($this->normalize($code));
        } catch (\PhpParser\Error $exception) {
            throw new ObfuscatorException(\sprintf('%s (строка %d)', $exception->getRawMessage(), $exception->getStartLine()), 0, $exception);
        }
    }

    private function normalize(string $code): string
    {
        $code = \trim($code);

        if (!\str_starts_with($code, '<?')) {
            $code = "<?php\n".$code;
        }

        if (\str_ends_with($code, '?>')) {
            $code = \substr($code, 0, -2);
        }

        return $code;
    }
}
