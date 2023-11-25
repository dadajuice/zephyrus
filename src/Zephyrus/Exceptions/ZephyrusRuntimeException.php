<?php namespace Zephyrus\Exceptions;

use RuntimeException;
use Throwable;

class ZephyrusRuntimeException extends RuntimeException
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct('ZEPHYRUS ' . $message, $code, $previous);
    }
}
