<?php namespace Zephyrus\Network;

class Uri
{
    /**
     * @var bool determines if request is under HTTPS
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
     * @var string port used for the requested uri (e.g. 8080)
     */
    private $port;

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
     * Removes the specified named argument from the given query string. Useful for replacement in the uri query string
     * like in a pager (e.g. remove the 'page' argument from the URI).
     *
     * @param string $query
     * @param string $name
     * @return string
     */
    public static function removeArgument(string $query, string $name): string
    {
        return preg_replace("/(" . $name . "=[0-9A-Za-z_-]*&?)/", "", $query);
    }

    public function __construct(string $uri)
    {
        $urlParts = parse_url($uri);
        $this->host = $urlParts['host'] ?? "";
        $this->scheme = $urlParts['scheme'] ?? "";
        $this->isSecure = $this->scheme == 'https';
        $this->username = $urlParts['user'] ?? "";
        $this->port = $urlParts['port'] ?? "";
        $this->password = $urlParts['pass'] ?? "";
        $this->path = $urlParts['path'] ?? "";
        $this->query = $urlParts['query'] ?? "";
        $this->fragment = $urlParts['fragment'] ?? "";
    }

    public function getBaseUrl()
    {
        $defaultPorts = ['http' => 80, 'https' => 443];
        return $this->getScheme() . '://'
            . $this->getHost()
            . (($this->getPort() != $defaultPorts[$this->getScheme()] && !empty($this->getPort())) ? ":" . $this->getPort() : "");
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
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }
}
