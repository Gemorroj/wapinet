<?php
namespace Wapinet\Bundle\Exception;

/**
 * Thrown whenever a client process fails.
 */
class WhoisException extends \RuntimeException
{
    public function __construct(array $messages, $code = 0, \Exception $previous = null)
    {
        parent::__construct(implode("\r\n", $messages), $code, $previous);
    }
}
