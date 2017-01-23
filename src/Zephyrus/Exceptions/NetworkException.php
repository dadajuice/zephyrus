<?php namespace Zephyrus\Exceptions;

class NetworkException extends \Exception
{
    private $httpCode;

    public function __construct($httpCode)
    {
        $this->httpCode = $httpCode;
        $message = 'Network error occured';
        switch ($httpCode) {
            case 404:
                $message = 'Not found';
                break;
            case 500:
                $message = 'Internal server error';
                break;
            case 403:
                $message = 'Forbidden';
                break;
            case 405:
                $message = 'Method not allowed';
                break;
            case 406:
                $message = 'Not acceptable';
                break;
        }
        parent::__construct($message);
    }

    public function getHttpStatusCode()
    {
        return $this->httpCode;
    }

    public function isNotFound()
    {
        return $this->httpCode == 404;
    }

    public function isInternalError()
    {
        return $this->httpCode == 500;
    }

    public function isForbidden()
    {
        return $this->httpCode == 403;
    }

    public function isMethodNotAllowed()
    {
        return $this->httpCode == 405;
    }

    public function isNotAcceptable()
    {
        return $this->httpCode == 406;
    }
}
