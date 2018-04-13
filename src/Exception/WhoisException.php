<?php
namespace App\Exception;

/**
 * Thrown whenever a client process fails.
 */
class WhoisException extends \RuntimeException
{
    /**
     * {@inheritdoc}
     */
    public function __construct(array $messages, $code = 0, \Exception $previous = null)
    {
        parent::__construct(implode("\r\n", $messages), $code, $previous);
    }
}
