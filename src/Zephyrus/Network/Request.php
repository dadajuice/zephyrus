<?php namespace Zephyrus\Network;

class Request
{
    /**
     * @var mixed[] every parameters included in the request
     */
    private $parameters = [];

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
     * @var string destined uri of the request
     */
    private $uri;

    /**
     * @var string destined full url of request
     */
    private $baseUrl;

    /**
     * @var boolean determines if request is under HTTPS
     */
    private $isSecure;

    /**
     * @var string scheme (protocol) of requested uri (e.g http)
     */
    private $scheme;

    /**
     * @var string hostname (domain or ip address) of requested uri (e.g example.com)
     */
    private $host;

    /**
     * @var string specified username of requested uri
     */
    private $username;

    /**
     * @var string specified password of requested uri
     */
    private $password;

    /**
     * @var string complete resource path of requested uri (e.g /example/foo)
     */
    private $path;

    /**
     * @var string query string of requested uri (e.g ?id=34&foo=1)
     */
    private $query;

    /**
     * @var string specified anchor of requested uri (e.g #example)
     */
    private $fragment;

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
     * @var mixed[] list of all uploaded files
     */
    private $files;

    public function __construct($uri = '', $method = '', $parameters = [], $cookies = [], $files = [], $server = [])
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->parameters = $parameters;
        $this->serverVariables = $server;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->initializeServer();
        $this->initializeUriParts();
        $this->initializeBaseUrl();
    }

    /**
     * @param string $name
     * @param string $default
     * @return string
     */
    public function getCookieValue($name, $default = null)
    {
        if (isset($this->cookies[$name])) {
            return $this->cookies[$name];
        }
        return $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCookie($name)
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
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function isSecure()
    {
        return $this->isSecure;
    }

    /**
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
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
     * @return mixed[]
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function getParameter(string $name, $default = null)
    {
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }
        return $default;
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
     * @return string
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
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    private function initializeServer()
    {
        $this->accept = $this->serverVariables['HTTP_ACCEPT'] ?? '';
        $this->userAgent = $this->serverVariables['HTTP_USER_AGENT'] ?? '';
        $this->clientIp = $this->serverVariables['REMOTE_ADDR'] ?? '';
    }

    private function initializeUriParts()
    {
        $urlParts = parse_url($this->uri);
        $this->host = $urlParts['host'] ?? null;
        $this->scheme = $urlParts['scheme'] ?? null;
        $this->isSecure = $this->scheme == 'https';
        $this->username = $urlParts['user'] ?? null;
        $this->password = $urlParts['pass'] ?? null;
        $this->path = $urlParts['path'] ?? null;
        $this->query = $urlParts['query'] ?? null;
        $this->fragment = $urlParts['fragment']?? null;
    }

    private function initializeBaseUrl()
    {
        $this->baseUrl = ($this->isSecure) ? 'https://' : 'http://';
        $this->baseUrl .= $this->host;
    }
}