<?php namespace Zephyrus\Network;

use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\Security\IntrusionDetectionException;
use Zephyrus\Exceptions\Security\InvalidCsrfException;
use Zephyrus\Exceptions\Security\MissingCsrfException;
use Zephyrus\Exceptions\Security\UnauthorizedAccessException;
use Zephyrus\Network\Request\CookieJar;
use Zephyrus\Network\Request\QueryString;
use Zephyrus\Network\Request\RequestAccept;
use Zephyrus\Network\Request\RequestBody;
use Zephyrus\Network\Request\RequestHistory;
use Zephyrus\Network\Router\RouteDefinition;
use Zephyrus\Security\AuthorizationGuard;
use Zephyrus\Security\CsrfGuard;
use Zephyrus\Security\IntrusionDetection;

class Request
{
    private ServerEnvironnement $environnement;
    private Url $url;
    private RequestBody $body;
    private RequestAccept $accept;
    private RequestHistory $history;
    private QueryString $queryString;
    private ?RouteDefinition $routeDefinition = null; // Exists only when the route is found in the repository ...
    private CookieJar $cookieJar;
    private IntrusionDetection $intrusionDetection;
    private CsrfGuard $csrfGuard;
    private AuthorizationGuard $authorizationGuard;

    public function __construct(ServerEnvironnement $environnement)
    {
        $this->environnement = $environnement;
        $this->url = new Url($environnement->getRequestedUrl());
        $this->queryString = $this->url->buildQueryString();
        $this->body = new RequestBody($environnement->getRawData(), $environnement->getContentType());
        $this->accept = new RequestAccept($environnement->getAccept());
        $this->cookieJar = new CookieJar($environnement->getCookies());
        $this->intrusionDetection = new IntrusionDetection($this, Configuration::getSecurity('ids'));
        $this->csrfGuard = new CsrfGuard($this, Configuration::getSecurity('csrf'));
        $this->authorizationGuard = new AuthorizationGuard($this);
        $this->history = new RequestHistory();
    }

    /**
     * Guards the request before processing by verifying if the intrusion detection system detected an activity over the
     * configured threshold or if the CSRF was not submitted. If the IDS is not enabled, it is ignored. Same with the
     * CSRF guard.
     *
     * @throws IntrusionDetectionException
     * @throws InvalidCsrfException
     * @throws MissingCsrfException
     * @throws UnauthorizedAccessException
     */
    public function guard(): void
    {
        if ($this->intrusionDetection->isEnabled()) {
            $this->intrusionDetection->run();
        }
        if ($this->csrfGuard->isEnabled()) {
            $this->csrfGuard->run();
        }
        $this->authorizationGuard->run();
    }

    /**
     * Retrieves the HTTP method of the request (either GET, POST, PUT, PATCH or DELETE).
     *
     * @return HttpMethod
     * @throws RouteMethodUnsupportedException
     */
    public function getMethod(): HttpMethod
    {
        return $this->body->getHttpMethodOverride() ?? $this->environnement->getMethod();
    }

    /**
     * Retrieves the requested route as it should be processed by the router (e.g. /users/4).
     *
     * @return string
     */
    public function getRoute(): string
    {
        return $this->url->getPath();
    }

    /**
     * Retrieves one specific parameter from the body data or query string. If the specified parameter doesn't exist,
     * the method returns the given default value (defaults to null).
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->queryString->getArgument($name) ?? $this->body->getParameter($name, $default);
    }

    /**
     * Retrieves the entire request parameters either given in query string or body.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return array_merge($this->queryString->getArguments(), $this->body->getParameters());
    }

    public function getRequestedUrl(): string
    {
        return $this->environnement->getRequestedUrl();
    }

    /**
     * Retrieves the complete request body with parsed parameter based on the content type.
     *
     * @return RequestBody
     */
    public function getBody(): RequestBody
    {
        return $this->body;
    }

    /**
     * Retrieves the accepted representation the client asks with the request.
     *
     * @return RequestAccept
     */
    public function getAccept(): RequestAccept
    {
        return $this->accept;
    }

    public function getCookieJar(): CookieJar
    {
        return $this->cookieJar;
    }

    /**
     * Retrieves the complete Uri instance for the current request. Meaning, the complete details of the url the client
     * requested.
     *
     * @return Url
     */
    public function getUrl(): Url
    {
        return $this->url;
    }

    public function getClientIp(): string
    {
        return $this->environnement->getClientIp();
    }

    public function getUserAgent(): string
    {
        return $this->environnement->getUserAgent();
    }

    /**
     * Returns only the last recorded visited GET route the client did within his active session. Should be considered
     * for returning to the previous visited URL.
     *
     * @return string
     */
    public function getReferer(): string
    {
        return $this->history->getReferer();
    }

    /**
     * Retrieves the entire route definition instance the client requested which contains the url, the arguments and
     * callbacks associated. Can be null if the router was not used.
     *
     * @return RouteDefinition | null
     */
    public function getRouteDefinition(): ?RouteDefinition
    {
        return $this->routeDefinition;
    }

    /**
     * Applies the matching route definition for the client request if any. The route definition contains essentially
     * what is needed for the URL arguments and the callback to execute.
     *
     * @param RouteDefinition $routeDefinition
     */
    public function setRouteDefinition(RouteDefinition $routeDefinition): void
    {
        $this->routeDefinition = $routeDefinition;
    }

    /**
     * Retrieves all the request headers the client sent.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->environnement->getHeaders();
    }

    /**
     * Retrieves a specific HTTP header from the request (case-insensitive).
     *
     * @param string $name
     * @param string|null $defaultValue
     * @return string|null
     */
    public function getHeader(string $name, ?string $defaultValue = null): ?string
    {
        return $this->environnement->getHeader($name) ?? $defaultValue;
    }

    public function getArgument(string $name, mixed $defaultValue = null): mixed
    {
        return $this->routeDefinition?->getArgument($name, $defaultValue) ?? null;
    }

    public function getArguments(): array
    {
        return $this->routeDefinition?->getArguments() ?? [];
    }

    public function getServerEnvironnement(): ServerEnvironnement
    {
        return $this->environnement;
    }

    public function getIntrusionDetection(): IntrusionDetection
    {
        return $this->intrusionDetection;
    }

    public function getCsrfGuard(): CsrfGuard
    {
        return $this->csrfGuard;
    }

    public function getHistory(): RequestHistory
    {
        return $this->history;
    }

    /**
     * Records the request into the client session history.
     */
    public function addToHistory(): void
    {
        $this->history->add($this);
    }

    public function getFiles(): array
    {
        return $_FILES;
    }
}
