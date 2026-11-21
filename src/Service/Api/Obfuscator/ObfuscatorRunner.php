<?php

declare(strict_types=1);

namespace App\Service\Api\Obfuscator;

use App\Service\PhpValidator;
use Psr\Log\LoggerInterface;

final readonly class ObfuscatorRunner
{
    public function __construct(
        private PhpCodeObfuscatorInterface $obfuscator,
        private PhpValidator $validator,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws ObfuscatorException when the obfuscated code is not valid PHP
     */
    public function run(
        string $code,
        bool $removeComments = true,
        ObfuscatorLevel $level = ObfuscatorLevel::Max,
    ): string {
        $result = $this->obfuscator->obfuscate($code, $removeComments, $level);
        $check = $this->validator->validateFragment($result);

        if (true !== ($check['validity'] ?? false)) {
            $this->logger->error('Obfuscated PHP failed syntax check.', [
                'errors' => $check['errors'] ?? [],
            ]);

            throw new ObfuscatorException('Результат обфускации не прошёл проверку синтаксиса. Попробуйте другой код.');
        }

        return $result;
    }
}
