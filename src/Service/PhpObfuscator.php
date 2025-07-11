<?php

namespace App\Service;

final readonly class PhpObfuscator
{
    public function obfuscate(string $code, bool $removeComments, bool $removeSpaces): string
    {
        $code = $this->cleanCode($code);

        if ($removeComments) {
            $code = \preg_replace("#(?<!:|\\\)//.*$#m", ' ', $code); // функция уничтожения комментариев
        }
        if ($removeSpaces) {
            $code = \preg_replace("/([\n\t\r\f]+)/i", ' ', $code); // функция уничтожения новых строк, прогонов, возврата каретки и табуляции
            $code = \preg_replace("/([\s]{2,}+)/i", ' ', $code); // функция уничтожения лишних пробелов
        }

        $code = 'eval(\urldecode(\base64_decode(\''.\base64_encode(\urlencode($code)).'\')));';
        $code = 'eval(\rawurldecode(\base64_decode(\''.\base64_encode(\rawurlencode($code)).'\')));';

        for ($i = 0, $l = \random_int(1, 3); $i <= $l; ++$i) {
            $code = 'eval(\gzinflate(\base64_decode(\''.\base64_encode(\gzdeflate($code, 6)).'\')));';
        }

        return '<?php eval(\gzinflate(\base64_decode(\''.\base64_encode(\gzdeflate($code, 9)).'\')));';
    }

    /**
     * Удаляем начальные и завершающие php теги.
     */
    private function cleanCode(string $code): string
    {
        $code = \trim($code);

        // Верх
        if (0 === \stripos($code, '<?php')) {
            $code = \substr($code, 5);
        } elseif (\str_starts_with($code, '<?')) {
            $code = \substr($code, 2);
        }
        // Низ
        if (\str_ends_with($code, '?>')) {
            $code = \substr($code, 0, -2);
        }

        return $code;
    }
}
