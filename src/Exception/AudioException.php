<?php

declare(strict_types=1);

namespace App\Exception;

class AudioException extends \RuntimeException
{
    private array $messages;

    public function __construct(array $messages, int $code = 0, \Exception $previous = null)
    {
        $this->messages = $messages;

        parent::__construct(\implode("\r\n", $messages), $code, $previous);
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
