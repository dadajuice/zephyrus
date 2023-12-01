<?php namespace Zephyrus\Exceptions\Mailer;

use Throwable;
use Zephyrus\Exceptions\ZephyrusException;

abstract class MailerException extends ZephyrusException
{
    /**
     * Groups all exception related to the Zephyrus internal PHP session wrapper. All children exception classes have a
     * code starting from 15000. All messages will be automatically prefixed by "ZEPHYRUS MAILER: ...".
     */
    public function __construct(string $message = "", int $code = 15000, ?Throwable $previous = null)
    {
        parent::__construct('MAILER: ' . $message, $code, $previous);
    }
}
