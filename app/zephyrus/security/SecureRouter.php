<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;
use Zephyrus\Network\Router;
use Zephyrus\Exceptions\InvalidCsrfException;

class SecureRouter extends Router
{
    /**
     * @var SecureRouter
     */
    private static $instance = null;

    /**
     * @return SecureRouter
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return string
     */
    public static function getRequestNonce()
    {
        return ContentSecurityPolicy::getRequestNonce();
    }

    /**
     * This method is automatically called when a route has been found before
     * any user defined code. This method sends security headers, runs the
     * IDS (if specified in config) and checks for CSRF token (if specified
     * in config).
     *
     * @param array $route
     * @throws InvalidCsrfException
     */
    protected function beforeCallback($route)
    {
        SecureHeader::getInstance()->send();
        CsrfFilter::getInstance()->filter();

        if (Configuration::getIdsConfiguration('active')) {
            IntrusionDetection::getInstance()->run();
        }
    }
}