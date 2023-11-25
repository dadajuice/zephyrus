<?php namespace Zephyrus\Exceptions;

use Exception;
use Throwable;

class ZephyrusException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('ZEPHYRUS ' . $message, $code, $previous);
    }
}
