<?php

declare(strict_types=1);

namespace App\Exception;

class WhoisException extends \RuntimeException
{
    public function __construct(array $messages, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(\implode("\r\n", $messages), $code, $previous);
    }
}
