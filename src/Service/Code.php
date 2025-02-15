<?php

namespace App\Service;

use App\Exception\CodeException;

final readonly class Code
{
    /**
     * @return array<string, string>
     */
    public function getAlgorithms(): array
    {
        return [
            'base64_encode' => 'Base64 encode',
            'base64_decode' => 'Base64 decode',
            'json_encode' => 'JSON encode',
            'json_decode' => 'JSON decode',
            'rawurlencode' => 'URL encode (RFC 3986)',
            'rawurldecode' => 'URL decode (RFC 3986)',
            'serialize' => 'PHP serialize',
            'htmlspecialchars_decode' => 'htmlspecialchars decode',
            'htmlspecialchars' => 'htmlspecialchars',
            'mb_strtolower' => 'Lower chars',
            'mb_strtoupper' => 'Upper chars',
        ];
    }

    /**
     * @throws CodeException
     */
    public function convertString(string $algorithm, string $string): string
    {
        if (!\array_key_exists($algorithm, $this->getAlgorithms())) {
            throw new CodeException('Неизвестный алгоритм кодирования');
        }

        $result = @$algorithm($string);

        if (\in_array($algorithm, ['json_decode', 'json_encode'], true)) {
            $this->checkJsonResult($result);
        } else {
            $this->checkResult($result);
        }

        return $result;
    }

    /**
     * @throws CodeException
     */
    private function checkJsonResult(?string $result): void
    {
        if (null === $result) {
            switch (\json_last_error()) {
                case \JSON_ERROR_DEPTH:
                    throw new CodeException('Достигнута максимальная глубина стека.');
                    break;

                case \JSON_ERROR_STATE_MISMATCH:
                    throw new CodeException('Неверный или не корректный JSON.');
                    break;

                case \JSON_ERROR_CTRL_CHAR:
                    throw new CodeException('Ошибка управляющего символа, возможно неверная кодировка.');
                    break;

                case \JSON_ERROR_SYNTAX:
                    throw new CodeException('Синтаксическая ошибка.');
                    break;

                case \JSON_ERROR_UTF8:
                    throw new CodeException('Некорректные символы UTF-8, возможно неверная кодировка.');
                    break;
            }
        }
    }

    /**
     * @throws CodeException
     */
    private function checkResult($result): void
    {
        if (false === $result) {
            throw new CodeException('Не удалось преобразовать данные выбранным алгоритмом.');
        }
    }

    public function convertFile(string $algorithm, string $fileName): string
    {
        throw new CodeException('Конвертирование файлов не поддерживается');
    }
}
