<?php

namespace App\Exception;

/**
 * Thrown whenever a client process fails.
 */
class AudioException extends \RuntimeException
{
    protected $messages = [];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $messages, $code = 0, \Exception $previous = null)
    {
        $this->messages = $messages;

        parent::__construct(\implode("\r\n", $messages), $code, $previous);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
