<?php namespace Zephyrus\Network;

use Zephyrus\Application\RouterEngine;

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
}