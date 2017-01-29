<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Router as BaseRouter;

class Router extends BaseRouter
{
    /**
     * @var CsrfGuard
     */
    private $csrfGuard;

    /**
     * @var SecureHeader
     */
    private $secureHeader;

    /**
     * @var Authorization
     */
    private $authorization;

    public function __construct()
    {
        $this->csrfGuard = new CsrfGuard();
        $this->secureHeader = new SecureHeader();
        $this->authorization = new Authorization();
    }

    /**
     * @return CsrfGuard
     */
    public function getCsrfGuard(): CsrfGuard
    {
        return $this->csrfGuard;
    }

    /**
     * @return SecureHeader
     */
    public function getSecureHeader(): SecureHeader
    {
        return $this->secureHeader;
    }

    /**
     * @return Authorization
     */
    public function getAuthorization(): Authorization
    {
        return $this->authorization;
    }

    /**
     * This method is automatically called when a route has been found before
     * any user defined code. This method sends security headers, runs the
     * IDS (if specified in config) and checks for CSRF token (if specified
     * in config).
     *
     * @param array $route
     * @throws UnauthorizedAccessException
     */
    protected function beforeCallback($route)
    {
        $failedRequirements = [];
        if (!$this->authorization->isAuthorized($route['uri'], $failedRequirements)) {
            throw new UnauthorizedAccessException($route['uri'], $failedRequirements);
        }
        $this->secureHeader->send();
        if (Configuration::getSecurityConfiguration('ids_enabled')) {
            IntrusionDetection::getInstance()->run();
        }
        if (Configuration::getSecurityConfiguration('csrf_guard_enabled')) {
            $this->csrfGuard->guard();
            if (Configuration::getSecurityConfiguration('csrf_guard_automatic_html')) {
                ob_start();
            }
        }
    }

    /**
     * This method is automatically called when a route's provided callback has
     * been called. For a normal html rendering route, it means the has already
     * been sent. This method is used to automatically inject CSRF token to any
     * forms the resulting HTML might have.
     *
     * @param array $route
     */
    protected function afterCallback($route)
    {
        if (Configuration::getSecurityConfiguration('csrf_guard_enabled')
            && Configuration::getSecurityConfiguration('csrf_guard_automatic_html')) {
            echo $this->csrfGuard->injectForms(ob_get_clean());
        }
    }
}
