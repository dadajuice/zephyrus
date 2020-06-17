<?php namespace Zephyrus\Security;

class CrossOriginResourcePolicy
{
    const ANY = "*";

    /**
     * Access-Control-Allow-Origin response header indicates whether the response can be shared with resources with the
     * given origin. If empty (the default), no header will be sent as the natural security configuration should deny
     * cross origin resource sharing.
     *
     * @var string
     */
    private $accessControlAllowOrigin = "";

    /**
     * Access-Control-Request-Headers response header (preflight OPTION) indicates which HTTP headers can be used during
     * the actual request. If empty, the header wont be sent. Can either be wildcard * or a comma separated list of
     * allowed headers (e.g Origin, X-Requested-With, Content-Type, Accept).
     *
     * @var string
     */
    private $accessControlAllowHeaders = "";

    /**
     * Access-Control-Request-Methods response header (preflight OPTION) indicates HTTP methods can be used during
     * the actual request. If empty, the header wont be sent. Can either be wildcard * or a comma separated list of
     * allowed methods (e.g GET, HEAD, PUT, PATCH, POST, DELETE).
     *
     * @var string
     */
    private $accessControlAllowMethods = "";

    /**
     * Access-Control-Max-Age response header indicates how long the Access-Control-Allow-Methods and Access-Control-
     * Allow-Headers headers can be cached.
     *
     * @var int
     */
    private $accessControlMaxAge = "";

    /**
     * The Access-Control-Allow-Credentials response header tells browsers whether to expose the response to frontend
     * JavaScript code when the request's credentials mode (Request.credentials) is include or xhr.withCredentials. If
     * empty, the header wont be sent.
     *
     * @var string
     */
    private $accessControlAllowCredentials = "";

    /**
     * The Access-Control-Expose-Headers response header identifies which HTTP headers can be exposed to the cross
     * origin request. If empty, wont be sent and then the browser will consider the default CORS safe listed headers.
     *
     * @var string
     */
    private $accessControlAllowExposeHeaders = "";

    /**
     * Send the Access-Control-Allow-* headers according to the defined properties. Will not send any if no origin is
     * specified.
     */
    public function send()
    {
        if (empty($this->accessControlAllowOrigin)) {
            return;
        }
        header('Access-Control-Allow-Origin: ' . $this->accessControlAllowOrigin);
        if (!empty($this->accessControlAllowHeaders)) {
            header('Access-Control-Allow-Headers: ' . $this->accessControlAllowHeaders);
        }
        if (!empty($this->accessControlAllowMethods)) {
            header('Access-Control-Allow-Methods: ' . $this->accessControlAllowMethods);
        }
        if (!empty($this->accessControlMaxAge)) {
            header('Access-Control-Max-Age: ' . $this->accessControlMaxAge);
        }
        if (!empty($this->accessControlAllowCredentials)) {
            header('Access-Control-Allow-Credentials: ' . $this->accessControlAllowCredentials);
        }
        if (!empty($this->accessControlAllowExposeHeaders)) {
            header('Access-Control-Expose-Headers: ' . $this->accessControlAllowExposeHeaders);
        }
    }

    /**
     * @return string
     */
    public function getAccessControlAllowOrigin(): string
    {
        return $this->accessControlAllowOrigin;
    }

    /**
     * Defines the Access-Control-Allow-Origin header to sent indicating if the response can be shared with the given
     * origin. To open to any domain, use the ANY constant (*). To restrict to one specific domain name, use the
     * url (e.g. https://example.com).
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
     * @param string $accessControlAllowOrigin
     */
    public function setAccessControlAllowOrigin(string $accessControlAllowOrigin)
    {
        $this->accessControlAllowOrigin = $accessControlAllowOrigin;
    }

    /**
     * @return int
     */
    public function getAccessControlMaxAge(): int
    {
        return $this->accessControlMaxAge;
    }

    /**
     * Defines the Access-Control-Max-Age header to sent indicating how long the *-allow-headers and *-allow-methods can
     * be cached in seconds.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Max-Age
     * @param int $seconds
     */
    public function setAccessControlMaxAge(int $seconds = 7200)
    {
        $this->accessControlMaxAge = $seconds;
    }

    /**
     * @return string
     */
    public function getAccessControlAllowHeaders(): string
    {
        return $this->accessControlAllowHeaders;
    }

    /**
     * Defines the Access-Control-Allow-Headers header to sent indicating the HTTP headers allowed for a cross origin
     * shared resource. Can wither be empty, * or a list of comma separated headers.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
     * @param string $accessControlAllowHeaders
     */
    public function setAccessControlAllowHeaders(string $accessControlAllowHeaders)
    {
        $this->accessControlAllowHeaders = $accessControlAllowHeaders;
    }

    /**
     * @return string
     */
    public function getAccessControlAllowMethods(): string
    {
        return $this->accessControlAllowMethods;
    }

    /**
     * Defines the Access-Control-Allow-Methods header to sent indicating the HTTP methods allowed for a cross origin
     * shared resource. Can either be empty, * or a list of comma separated methods.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
     * @param string $accessControlAllowMethods
     */
    public function setAccessControlAllowMethods(string $accessControlAllowMethods)
    {
        $this->accessControlAllowMethods = $accessControlAllowMethods;
    }

    /**
     * @return string
     */
    public function getAccessControlAllowCredentials(): string
    {
        return $this->accessControlAllowCredentials;
    }

    /**
     * Defines the Access-Control-Allow-Credentials header to sent indicating if the response can be exposed to front
     * JavaScript using the credential mode. Can either be empty, "true" or "false".
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Credentials
     * @param string $accessControlAllowCredentials
     */
    public function setAccessControlAllowCredentials(string $accessControlAllowCredentials)
    {
        $this->accessControlAllowCredentials = $accessControlAllowCredentials;
    }

    /**
     * @return string
     */
    public function getAccessControlAllowExposeHeaders(): string
    {
        return $this->accessControlAllowExposeHeaders;
    }

    /**
     * Defines the Access-Control-Expose-Headers header to sent indicating which HTTP headers can be exposed from a
     * cross origin request. By default, the 7 CORS safe listed header is allowed (Cache-Control, Content-Language,
     * Content-Length, Content-Type, Expires, Last-Modified, Pragma). Can either be empty, * or a list of comma
     * separated headers.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
     * @param string $accessControlAllowExposeHeaders
     */
    public function setAccessControlAllowExposeHeaders(string $accessControlAllowExposeHeaders)
    {
        $this->accessControlAllowExposeHeaders = $accessControlAllowExposeHeaders;
    }
}
