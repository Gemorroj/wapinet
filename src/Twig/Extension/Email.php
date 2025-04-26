<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Email extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('wapinet_email_obfuscate', $this->obfuscate(...)),
        ];
    }

    /**
     * obfuscate a string to prevent spam-bots from sniffing it.
     *
     * @see https://github.com/octobercms/library/blob/v3.7.11/src/Html/HtmlBuilder.php#L370
     */
    public function obfuscate(string $value): string
    {
        $safe = '';

        foreach (\str_split($value) as $letter) {
            if (\ord($letter) > 128) {
                return $letter;
            }

            // To properly obfuscate the value, we will randomly convert each letter to
            // its entity or hexadecimal representation, keeping a bot from sniffing
            // the randomly obfuscated letters out of the string on the responses.
            switch (\random_int(1, 3)) {
                case 1:
                    $safe .= '&#'.\ord($letter).';';
                    break;

                case 2:
                    $safe .= '&#x'.\dechex(\ord($letter)).';';
                    break;

                case 3:
                    $safe .= $letter;
            }
        }

        return \str_replace('@', '&#64;', $safe);
    }
}
