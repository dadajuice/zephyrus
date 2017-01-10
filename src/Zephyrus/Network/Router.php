<?php namespace Zephyrus\Network;

use Zephyrus\Application\Configuration;
use Zephyrus\Application\RouterEngine;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Security\Authorization;
use Zephyrus\Security\CsrfGuard;
use Zephyrus\Security\IntrusionDetection;
use Zephyrus\Security\SecureHeader;

class Router extends RouterEngine
{
    /**
     * Add a new GET route for the application. The GET method must be
     * used to represent a specific resource (or collection) in some
     * representational format (HTML, JSON, XML, ...). Normally, a GET
     * request must only present data and not alter them in any way.
     *
     * E.g. GET /books
     *      GET /book/{id}
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array | null $acceptedRequestFormats
     */
    public function get($uri, $callback, $acceptedRequestFormats = null)
    {
        parent::addRoute('GET', $uri, $callback, $acceptedRequestFormats);
    }

    /**
     * Add a new POST route for the application. The POST method must be
     * used to create a new entry in a collection. Rarely used on a
     * specific resource.
     *
     * E.g. POST /books
     *
     * @param string $uri
     * @param callable $callback
     * @param string| array | null $acceptedRequestFormats
     */
    public function post($uri, $callback, $acceptedRequestFormats = null)
    {
        parent::addRoute('POST', $uri, $callback, $acceptedRequestFormats);
    }

    /**
     * Add a new PUT route for the application. The PUT method must be
     * used to update a specific resource (or collection) and must be
     * considered idempotent.
     *
     * E.g. PUT /book/{id}
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array | null $acceptedRequestFormats
     */
    public function put($uri, $callback, $acceptedRequestFormats = null)
    {
        parent::addRoute('PUT', $uri, $callback, $acceptedRequestFormats);
    }

    /**
     * Add a new DELETE route for the application. The DELETE method must
     * be used only to delete a specific resource (or collection) and must
     * be considered idempotent.
     *
     * E.g. DELETE /book/{id}
     *      DELETE /books
     *
     * @param string $uri
     * @param callable $callback
     * @param string | array | null $acceptedRequestFormats
     */
    public function delete($uri, $callback, $acceptedRequestFormats = null)
    {
        parent::addRoute('DELETE', $uri, $callback, $acceptedRequestFormats);
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
        /*$failedRequirements = [];
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
        }*/
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
        /*if (Configuration::getSecurityConfiguration('csrf_guard_enabled')
            && Configuration::getSecurityConfiguration('csrf_guard_automatic_html')) {
            echo CsrfGuard::getInstance()->injectForms(ob_get_clean());
        }*/
    }
}