<?php namespace Zephyrus\Exceptions\Security;

use Throwable;
use Zephyrus\Exceptions\ZephyrusException;

abstract class SecurityException extends ZephyrusException
{
    /**
     * Groups all exception related to the Zephyrus security. All children exception classes have a code starting
     * from 14000. All messages will be automatically prefixed by "ZEPHYRUS SECURITY: ...".
     */
    public function __construct(string $message = "", int $code = 14000, ?Throwable $previous = null)
    {
        parent::__construct('SECURITY: ' . $message, $code, $previous);
    }
}
