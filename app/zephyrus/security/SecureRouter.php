<?php namespace Zephyrus\Security;

use Zephyrus\Application\Configuration;
use Zephyrus\Network\Router;
use Zephyrus\Exceptions\InvalidCsrfException;

class SecureRouter extends Router
{
    /**
     * @var array
     */
    private $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = Configuration::getSecurityConfiguration();
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
        /*
        if (Configuration::getIdsConfiguration('active')) {
            IntrusionDetection::getInstance()->run();
        }*/

        if ($this->config['csrf_guard_enabled']) {
            CsrfGuard::getInstance()->guard();
            if ($this->config['csrf_guard_automatic_html']) {
                ob_start();
            }
        }
    }

    /**
     * Method called immediately after calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made right after the route callback.
     *
     * @param array $route
     */
    protected function afterCallback($route)
    {
        if ($this->config['csrf_guard_enabled'] && $this->config['csrf_guard_automatic_html']) {
            echo CsrfGuard::getInstance()->injectForms(ob_get_clean());
        }
    }
}