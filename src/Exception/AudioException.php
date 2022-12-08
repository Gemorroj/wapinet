<?php

declare(strict_types=1);

namespace App\Exception;

class AudioException extends \RuntimeException
{
    public function __construct(private array $messages, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(\implode("\r\n", $messages), $code, $previous);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
