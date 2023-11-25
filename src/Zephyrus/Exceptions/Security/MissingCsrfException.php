<?php namespace Zephyrus\Exceptions\Security;

use Zephyrus\Network\Request;

class MissingCsrfException extends CsrfException
{
    public function __construct(Request $request)
    {
        $url = $request->getMethod()->value . ' ' . $request->getRoute();
        parent::__construct("The submitted form is missing the needed CSRF tokens. The requested route [$url] is configured to proceed the CSRF mitigation. If you think this is not the case, you can add the route to the CSRF exceptions, use the 'nocsrf' attribute on the &lt;form&gt; or disable the feature.", 14003);
        $this->route = $request->getRoute();
        $this->method = $request->getMethod();
    }
}
