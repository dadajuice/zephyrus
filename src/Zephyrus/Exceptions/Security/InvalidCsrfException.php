<?php namespace Zephyrus\Exceptions\Security;

use Zephyrus\Network\Request;

class InvalidCsrfException extends CsrfException
{
    public function __construct(Request $request)
    {
        $url = $request->getMethod()->value . ' ' . $request->getRoute();
        parent::__construct("The provided CSRF token for the requested route [$url] is invalid or has expired.", 14002);
        $this->route = $request->getRoute();
        $this->method = $request->getMethod();
    }
}
