<?php namespace Zephyrus\Network;

use Zephyrus\Network\Request\QueryString;

class Url
{
    private const DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    private bool $isSecure;
    private string $scheme;
    private string $host;
    private string $port;
    private string $username;
    private string $password;
    private string $path;
    private string $query;
    private string $fragment;
    private string $rawUrl;

    public function __construct(string $url)
    {
        $urlParts = parse_url($url);
        $this->host = $urlParts['host'] ?? "";
        $this->scheme = $urlParts['scheme'] ?? "";
        $this->isSecure = $this->scheme == 'https';
        $this->username = $urlParts['user'] ?? "";
        $this->port = $urlParts['port'] ?? self::DEFAULT_PORTS[$this->scheme];
        $this->password = $urlParts['pass'] ?? "";
        $this->path = $urlParts['path'] ?? "";
        $this->query = $urlParts['query'] ?? "";
        $this->fragment = $urlParts['fragment'] ?? "";
        $this->rawUrl = $url;
    }

    /**
     * Generates the base URL based on the configured uri. Will return everything up to the path segment. For example,
     * a configured uri 'https://domain.test/toto/4' would give a base url of 'https://domain.test'. This make sure to
     * include the port address if needed.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->getScheme() . '://'
            . $this->getHost()
            . (($this->getPort() != self::DEFAULT_PORTS[$this->getScheme()] && !empty($this->getPort())) ? ":" . $this->getPort() : "");
    }

    /**
     * Builds a QueryString instance which allows to do modification within the query string easily (e.g. remove an
     * argument or add one).
     *
     * @return QueryString
     */
    public function buildQueryString(): QueryString
    {
        return new QueryString($this->query);
    }

    /**
     * Retrieves the hostname (domain or ip address) of the requested uri (e.g. example.com).
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Retrieves the complete resource path of requested uri (e.g /example/foo).
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Determines if request is under HTTPS.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->isSecure;
    }

    /**
     * Retrieves the scheme (protocol) of the requested uri (e.g. http).
     *
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Retrieves the specified username of requested uri.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Retrieves the specified password of requested uri.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Retrieves the specified anchor of the requested uri (e.g #example).
     *
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * Retrieves the raw query string of requested uri (e.g ?id=34&foo=1).
     *
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * Retrieves the port used for the requested uri (e.g. 8080).
     *
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * Retrieves the configured Uri as it was given to the constructor.
     *
     * @return string
     */
    public function getRawUrl(): string
    {
        return $this->rawUrl;
    }
}
