<?php namespace Zephyrus\Network;

class Request
{
    /**
     * @var array every body parameters included in the request
     */
    private $parameters = [];

    /**
     * @var array every route parameters found from the routing process
     */
    private $arguments = [];

    /**
     * @var string HTTP method used by client
     */
    private $method;

    /**
     * @var string ip address of originated request
     */
    private $clientIp;

    /**
     * @var string accepted representation from the client
     */
    private $accept;

    /**
     * @var Uri destined uri of the request
     */
    private $uri;

    /**
     * @var string Uri of where the client initiated the current request
     */
    private $referer;

    /**
     * @var string requested uri as it was received (e.g demo.local/users/1)
     */
    private $requestedUri;

    /**
     * @var string destined full url of request
     */
    private $baseUrl;

    /**
     * @var string specified user agent (e.g Chrome)
     */
    private $userAgent;

    /**
     * @var mixed[] list of all server variables ($_SERVER)
     */
    private $serverVariables;

    /**
     * @var mixed[] list of all specified cookies
     */
    private $cookies;

    /**
     * @var mixed[] list of all HTTP headers
     */
    private $headers;

    /**
     * @var mixed[] list of all uploaded files
     */
    private $files;

    /**
     * Request constructor which need the option array data to populate the
     * request. E.g.
     *
     * 'parameters' => ['t' => 1, 'z' => 5],
     * 'cookies' => $_COOKIE,
     * 'files' => $_FILES,
     * 'server' => $_SERVER,
     * 'headers' => getallheaders()
     *
     * @param string $uri
     * @param string $method
     * @param array $options
     */
    public function __construct(string $uri, string $method, array $options = [])
    {
        $this->uri = new Uri($uri);
        $this->requestedUri = $uri;
        $this->method = $method;
        $this->parameters = $options['parameters'] ?? [];
        $this->serverVariables = $options['server'] ?? [];
        $this->cookies = $options['cookies'] ?? [];
        $this->files = $options['files'] ?? [];
        $this->headers = $options['headers'] ?? [];
        $this->initializeServer();
        $this->initializeBaseUrl();
    }

    /**
     * @param string $name
     * @param string $defaultValue
     * @return string
     */
    public function getCookieValue(string $name, ?string $defaultValue = null): ?string
    {
        return (isset($this->cookies[$name])) ? $this->cookies[$name] : $defaultValue;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return isset($this->cookies[$name]);
    }

    /**
     * @return mixed[]
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param $name
     * @return mixed[]
     */
    public function getFile($name)
    {
        if (isset($this->files[$name])) {
            return $this->files[$name];
        }
        return null;
    }

    /**
     * @return mixed[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @return string
     */
    public function getClientIp()
    {
        return $this->clientIp;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function prependParameter($name, $value)
    {
        $this->parameters = array_merge([$name => $value], $this->parameters);
    }

    /**
     * Retrieves the entire body parameters.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Retrieves one specific parameter from the body data. If the specified parameter doesn't exists, the method
     * returns the given default value (default to null).
     *
     * @param string $name
     * @param mixed | null $default
     * @return mixed | null
     */
    public function getParameter(string $name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }
        return $default;
    }

    /**
     * Retrieves the parameters used within the route resolution. In example, the route "/users/{userId}/logs/{logId}"
     * would produce two arguments : userId and logId with their values assigned.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Retrieves one specific parameter from the route resolution. If the specified parameter doesn't exists, the method
     * returns the given default value (default to null).
     *
     * @param string $name
     * @param mixed | null $default
     * @return mixed | null
     */
    public function getArgument(string $name, $default = null)
    {
        if (isset($this->arguments[$name])) {
            return $this->arguments[$name];
        }
        return $default;
    }

    /**
     * Adds a route resolution parameter. Done automatically by the Router class when evaluating controller method to
     * execute.
     *
     * @param string $name
     * @param mixed $value
     */
    public function addArgument(string $name, $value)
    {
        $this->arguments[$name] = $value;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getAccept()
    {
        return $this->accept;
    }

    /**
     * Retrieves the defined accepted representations order by specified priority using the standard parameter "q" which
     * should range from 0 (lowest) to 1 (highest).
     *
     * @return array
     */
    public function getAcceptedRepresentations(): array
    {
        $acceptedRepresentations = explode(',', $this->accept);
        array_walk($acceptedRepresentations, function (&$accept) {
            // When no priority parameter is given, use natural defined order
            // by adding q=1.
            if (strpos($accept, ';q') === false) {
                $accept .= ';q=1';
            }
            $accept = explode(';q=', $accept);
        });
        usort($acceptedRepresentations, function ($a, $b) {
            // Sort using priority parameters
            return $b[1] <=> $a[1];
        });
        usort($acceptedRepresentations, function ($a, $b) {
            // Sort using specificity (*/*) for same priority
            if ($a[1] == $b[1]) {
                return substr_count($a[0], '*') <=> substr_count($b[0], '*');
            }
            return 0;
        });
        return array_filter(array_column($acceptedRepresentations, 0));
    }

    /**
     * @return Uri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->getUri()->getPath();
    }

    /**
     * @return string
     */
    public function getRequestedUri(): string
    {
        return $this->requestedUri;
    }

    /**
     * @return string
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * @return string[]
     */
    public function getServerVariables(): array
    {
        return $this->serverVariables;
    }

    /**
     * @param string $name
     * @param null|string $defaultValue
     * @return null|string
     */
    public function getServerVariable(string $name, ?string $defaultValue = null): ?string
    {
        return $this->serverVariables[$name] ?? $defaultValue;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
    }

    private function initializeServer()
    {
        $this->accept = $this->serverVariables['HTTP_ACCEPT'] ?? '';
        $this->userAgent = $this->serverVariables['HTTP_USER_AGENT'] ?? '';
        $this->clientIp = $this->serverVariables['REMOTE_ADDR'] ?? '';
        $this->referer = $this->serverVariables['HTTP_REFERER'] ?? '';
    }

    private function initializeBaseUrl()
    {
        $this->baseUrl = $this->uri->getBaseUrl();
    }
}
