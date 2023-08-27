<?php namespace Zephyrus\Network;

use Zephyrus\Utilities\Cache;

class RouteRepository
{
    private const CACHE_ROUTE_KEY = '__router_repository';
    private const CACHE_UPDATE_KEY = '__router_repository_update_time';

    /**
     * Associative array that contains all defined routes. Routes are organized by HTTP method as main key, value is an
     * array of stdClass representing the route.
     *
     * @var array
     */
    private array $routes = [];

    /**
     * APCu php cache to keep a reference to all the defined routes.
     *
     * @var Cache
     */
    private Cache $cache;

    /**
     * APCu php cache to keep a reference to the last time the route were updated.
     *
     * @var Cache
     */
    private Cache $cacheUpdate;

    public function __construct()
    {
        $this->cache = new Cache(self::CACHE_ROUTE_KEY);
        $this->cacheUpdate = new Cache(self::CACHE_UPDATE_KEY);
    }

    /**
     * Verifies if the currently cached route definitions (if it exists) are outdated and need regeneration. Returns
     * true if the given time is newer than the cache creation time or if no cache exist.
     *
     * @param int $time
     * @return bool
     */
    public function isCacheOutdated(int $time): bool
    {
        if (!$this->cache->exists()) {
            return true;
        }
        $lastUpdate = $this->cacheUpdate->read() ?? 0;
        return $lastUpdate < $time;
    }

    /**
     * Saves the instance currently defined routes into the APCu PHP cache. To optimize futur calls, the method
     * initializeFromCache() should be called.
     *
     * @return void
     */
    public function cache(): void
    {
        $this->cache->cache($this->routes);
        $this->cacheUpdate->cache(time());
    }

    /**
     * Initializes the application route definitions from the APCu PHP cache and thus avoiding unnecessary looping
     * through the various project controllers.
     *
     * @return void
     */
    public function initializeFromCache(): void
    {
        $this->routes = $this->cache->read() ?? [];
    }

    public function getRoutes(?string $forHttpMethod = null): array
    {
        if ($forHttpMethod) {
            if (array_key_exists($forHttpMethod, $this->routes)) {
                return $this->routes[$forHttpMethod];
            }
            return [];
        }
        return $this->routes;
    }

    public function findRoutes(string $httpMethod, string $requestedUri): array
    {
        $routes = $this->getRoutes($httpMethod);
        $matchingRoutes = [];
        foreach ($routes as $routeDefinition) {
            if ($routeDefinition->route->match($requestedUri)) {
                $matchingRoutes[] = $routeDefinition;
            }
        }
        if (!empty($matchingRoutes)) {
            usort($matchingRoutes, function ($a, $b) {
                return strcmp($a->route->getUri(), $b->route->getUri());
            });
        }
        return $matchingRoutes;
    }

    /**
     * Adds a new GET route for the application. The GET method must be used to represent a specific resource (or
     * collection) in some representational format (HTML, JSON, XML, ...). Normally, a GET request must only present
     * data and not alter them in any way.
     *
     * E.g. GET /books
     *      GET /book/{id}
     *
     * @param string $uri
     * @param callable | array $callback
     * @param string | array $acceptedFormats
     */
    public function get(string $uri, callable|array $callback, string|array $acceptedFormats = ContentType::ANY): void
    {
        $this->addRoute('GET', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new POST route for the application. The POST method must be used to create a new entry in a collection. It
     * is rarely used on a specific resource.
     *
     * E.g. POST /books
     *
     * @param string $uri
     * @param callable | array $callback
     * @param string | array $acceptedFormats
     */
    public function post(string $uri, callable|array $callback, string|array $acceptedFormats = ContentType::ANY): void
    {
        $this->addRoute('POST', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new PUT route for the application. The PUT method must be used to update a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. PUT /book/{id}
     *
     * @param string $uri
     * @param callable | array $callback
     * @param string | array $acceptedFormats
     */
    public function put(string $uri, callable|array $callback, string|array $acceptedFormats = ContentType::ANY): void
    {
        $this->addRoute('PUT', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new PATCH route for the application. The PATCH method must be used to update a specific resource or
     * collection and must be considered idempotent. Should be used instead of PUT when it is possible to update only
     * given fields to update and not the entire resource.
     *
     * E.g. PATCH /book/{id}
     *
     * @param string $uri
     * @param callable | array $callback
     * @param string | array $acceptedFormats
     */
    public function patch(string $uri, callable|array $callback, string|array $acceptedFormats = ContentType::ANY): void
    {
        $this->addRoute('PATCH', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new DELETE route for the application. The DELETE method must be used only to delete a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. DELETE /book/{id}
     *      DELETE /books
     *
     * @param string $uri
     * @param callable | array $callback
     * @param string | array $acceptedFormats
     */
    public function delete(string $uri, callable|array $callback, string|array $acceptedFormats = ContentType::ANY): void
    {
        $this->addRoute('DELETE', $uri, $callback, $acceptedFormats);
    }

    /**
     * Adds a new route for the application. Make sure to create the adequate structure with corresponding parameters
     * regex pattern if needed. If the callback comes from a controller, it must be given as an array form [instance,
     * 'methodName'].
     *
     * @param string $method
     * @param string $uri
     * @param array|callable $callback
     * @param string | array $acceptedFormats
     */
    private function addRoute(string $method, string $uri, array|callable $callback, string|array $acceptedFormats): void
    {
        $this->routes[$method][] = (object) [
            'route' => new Route($uri),
            'controllerClass' => (is_array($callback)) ? $callback[0]::class : null,
            'controllerMethod' => (is_array($callback)) ? $callback[1] : null,
            'callback' => (is_array($callback)) ? null : $callback,
            'acceptedRequestFormats' => $acceptedFormats
        ];
    }
}
