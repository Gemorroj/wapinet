<?php
namespace WapinetBundle\Helper;

use WapinetBundle\Exception\CodeException;
/**
 * Code хэлпер
 */
class Code
{
    /**
     * @return array
     */
    public function getAlgorithms()
    {
        return array(
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
        );
    }


    /**
     * @param string $algorithm
     * @param string $string
     * @return string
     * @throws CodeException
     */
    public function convertString($algorithm, $string)
    {
        if (!\array_key_exists($algorithm, $this->getAlgorithms())) {
            throw new CodeException('Неизвестный алгоритм кодирования');
        }

        $result = @$algorithm($string);

        if (\in_array($algorithm, array('json_decode', 'json_encode'), true)) {
            $this->checkJsonResult($result);
        } else {
            $this->checkResult($result);
        }

        return $result;
    }

    /**
     * @param string $result
     * @throws CodeException
     */
    private function checkJsonResult($result)
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
     * @param mixed $result
     * @throws CodeException
     */
    private function checkResult($result)
    {
        if (false === $result) {
            throw new CodeException('Не удалось преобразовать данные выбранным алгоритмом.');
        }
    }

    /**
     * @param string $algorithm
     * @param string $fileName
     * @return string
     */
    public function convertFile($algorithm, $fileName)
    {
        throw new CodeException('Конвертирование файлов не поддерживается');
    }
}
