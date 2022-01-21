<?php namespace Zephyrus\Exceptions;

use Zephyrus\Network\Request;

class InvalidCsrfException extends \Exception
{
    public const ERROR_MISSING_TOKEN = 901;
    public const ERROR_INVALID_TOKEN = 902;

    /**
     * Keeps a reference to the Request instance that triggered the CSRF mitigation.
     *
     * @var Request
     */
    private Request $request;

    public function __construct(int $code, Request $request)
    {
        $this->request = $request;
        parent::__construct($this->codeToMessage($code), $code);
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
            self::ERROR_MISSING_TOKEN => "The submitted form is missing the needed CSRF tokens. The requested route [" . strtoupper($this->request->getMethod()) . ' ' . $this->request->getRoute() . "] is configured to proceed the CSRF mitigation. If you think this is not the case, you can add the route to the CSRF exceptions, use the 'nocsrf' attribute on the &lt;form&gt; or disable the feature.",
            self::ERROR_INVALID_TOKEN => "The provided CSRF token is invalid or has expired.",
        };
    }
}
