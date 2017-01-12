<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Router as BaseRouter;

class Router extends BaseRouter
{
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
        if (!Authorization::getInstance()->isAuthorized($route, $failedRequirements)) {
            throw new UnauthorizedAccessException($route['uri'], $failedRequirements);
        }
        if (Configuration::getSecurityConfiguration('ids_enabled')) {
            IntrusionDetection::getInstance()->run();
        }
        SecureHeader::getInstance()->send();
        if (Configuration::getSecurityConfiguration('csrf_guard_enabled')) {
            CsrfGuard::getInstance()->guard();
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
            echo CsrfGuard::getInstance()->injectForms(ob_get_clean());
        }
    }
}
