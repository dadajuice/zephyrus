<?php namespace Zephyrus\Exceptions;

class HttpRequestException extends \Exception
{
    public function __construct($message, $method, $url)
    {
        parent::__construct("Error while performing HTTP Request to url [$url] in [$method] method : " . $message);
    }
}
