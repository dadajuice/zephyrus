<?php namespace Zephyrus\Exceptions\Session;

use Throwable;
use Zephyrus\Exceptions\ZephyrusException;

abstract class SessionException extends ZephyrusException
{
    /**
     * Groups all exception related to the Zephyrus internal PHP session wrapper. All children exception classes have a
     * code starting from 13000. All message will be automatically prefixed by "ZEPHYRUS SESSION: ...".
     */
    public function __construct(string $message = "", int $code = 13000, ?Throwable $previous = null)
    {
        parent::__construct('SESSION: ' . $message, $code, $previous);
    }
}
