<?php

declare(strict_types=1);

namespace App\Exception;

class AudioException extends \RuntimeException
{
    /**
     * @param string[] $messages
     */
    public function __construct(private readonly array $messages, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(\implode("\r\n", $messages), $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
