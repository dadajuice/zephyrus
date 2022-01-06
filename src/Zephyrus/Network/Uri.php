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

    public function __construct(string $uri)
    {
        $urlParts = parse_url($uri);
        $this->host = $urlParts['host'] ?? null;
        $this->scheme = $urlParts['scheme'] ?? null;
        $this->isSecure = $this->scheme == 'https';
        $this->username = $urlParts['user'] ?? null;
        $this->port = $urlParts['port'] ?? null;
        $this->password = $urlParts['pass'] ?? null;
        $this->path = $urlParts['path'] ?? null;
        $this->query = $urlParts['query'] ?? null;
        $this->fragment = $urlParts['fragment'] ?? null;
    }

    public function getBaseUrl()
    {
        $defaultPorts = ['http' => 80, 'https' => 443];
        return $this->getScheme() . '://'
            . $this->getHost()
            . (($this->getPort() != $defaultPorts[$this->getScheme()] && !is_null($this->getPort())) ? ":" . $this->getPort() : "");
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
