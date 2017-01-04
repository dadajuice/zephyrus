<?php namespace Zephyrus\Network;

class Request
{
    /**
     * @var mixed[] every parameters included in the request
     */
    private static $parameters = [];

    /**
     * @var string HTTP method used by client
     */
    private static $method;

    /**
     * @var string ip address of originated request
     */
    private static $clientIp;

    /**
     * @var string accepted representation from the client
     */
    private static $accept;

    /**
     * @var string destined uri of the request
     */
    private static $uri;

    /**
     * @var string destined full url of request
     */
    private static $baseUrl;

    /**
     * @var boolean determines if request is under HTTPS
     */
    private static $isSecure;

    /**
     * @var string scheme (protocol) of requested uri (e.g http)
     */
    private static $scheme;

    /**
     * @var string hostname (domain or ip address) of requested uri (e.g example.com)
     */
    private static $host;

    /**
     * @var string specified username of requested uri
     */
    private static $username;

    /**
     * @var string specified password of requested uri
     */
    private static $password;

    /**
     * @var string complete resource path of requested uri (e.g /example/foo)
     */
    private static $path;

    /**
     * @var string query string of requested uri (e.g ?id=34&foo=1)
     */
    private static $query;

    /**
     * @var string specified anchor of requested uri (e.g #example)
     */
    private static $fragment;

    /**
     * @var mixed[] list of all specified cookies
     */
    private static $cookies;

    /**
     * @var mixed[] list of all uploaded files
     */
    private static $files;

    /**
     * Automatically load every data of the current HTTP request.
     */
    private static function initialize()
    {
        if (empty(self::$uri)) {
            self::$uri = $_SERVER['REQUEST_URI'];
            if (self::$uri != '/') {
                self::$uri = rtrim(self::$uri, '/');
            }
            $urlParts = parse_url(self::$uri);
            self::$scheme = (isset($urlParts['scheme']))
                ? $urlParts['scheme']
                : null;
            self::$host = $_SERVER['HTTP_HOST'];
            self::$username = (isset($urlParts['username']))
                ? $urlParts['username']
                : null;
            self::$password = (isset($urlParts['password']))
                ? $urlParts['password']
                : null;
            self::$path = (isset($urlParts['path']))
                ? $urlParts['path']
                : null;
            self::$query = $_SERVER["QUERY_STRING"];
            self::$fragment = (isset($urlParts['fragment']))
                ? $urlParts['fragment']
                : null;
            self::$method = strtoupper($_SERVER['REQUEST_METHOD']);
            self::$accept = (isset($_SERVER['HTTP_ACCEPT'])) ? $_SERVER['HTTP_ACCEPT'] : '';
            self::$isSecure = isset($_SERVER['HTTPS']);
            self::$clientIp = (getenv('HTTP_X_FORWARDED_FOR'))
                ? getenv('HTTP_X_FORWARDED_FOR')
                : getenv('REMOTE_ADDR');
            self::addParametersFromRequestMethod();
            self::$cookies = $_COOKIE;
            self::$files = $_FILES;

            self::$baseUrl = (self::$isSecure) ? 'https://' : 'http://';
            self::$baseUrl .= self::$host;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    public static function getCookieValue($name)
    {
        self::initialize();
        if (isset(self::$cookies[$name])) {
            return self::$cookies[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function hasCookie($name)
    {
        self::initialize();
        return isset(self::$cookies[$name]);
    }

    /**
     * @return mixed[]
     */
    public static function getCookies()
    {
        self::initialize();
        return self::$cookies;
    }

    /**
     * @param $name
     * @return mixed[]
     */
    public static function getFile($name)
    {
        self::initialize();
        if (isset(self::$files[$name])) {
            return self::$files[$name];
        }
        return null;
    }

    /**
     * @return mixed[]
     */
    public static function getFiles()
    {
        self::initialize();
        return self::$files;
    }

    /**
     * @return string
     */
    public static function getClientIp()
    {
        self::initialize();
        return self::$clientIp;
    }

    /**
     * @return string
     */
    public static function getQuery()
    {
        self::initialize();
        return self::$query;
    }

    /**
     * @return string
     */
    public static function getPath()
    {
        self::initialize();
        return self::$path;
    }

    /**
     * @return bool
     */
    public static function isSecure()
    {
        self::initialize();
        return self::$isSecure;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public static function addParameter($name, $value)
    {
        self::initialize();
        self::$parameters[$name] = $value;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public static function prependParameter($name, $value)
    {
        self::initialize();
        self::$parameters = array_merge([$name => $value], self::$parameters);
    }

    /**
     * @return mixed[]
     */
    public static function getParameters()
    {
        self::initialize();
        return self::$parameters;
    }

    /**
     * @param string $name
     * @return string | null
     */
    public static function getParameter($name)
    {
        self::initialize();
        if (isset(self::$parameters[$name])) {
            return self::$parameters[$name];
        }
        return null;
    }

    /**
     * @return string
     */
    public static function getMethod()
    {
        self::initialize();
        return self::$method;
    }

    /**
     * @return string
     */
    public static function getAccept()
    {
        self::initialize();
        return self::$accept;
    }

    /**
     * @return string
     */
    public static function getUri()
    {
        self::initialize();
        return self::$uri;
    }

    /**
     * @return string
     */
    public static function getUserAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * @return string[]
     */
    public static function getUriSegments()
    {
        self::initialize();
        $uri = self::$uri;
        $results = ['index'];
        if ($uri != '/') {
            $uri = trim($uri, '/');
            $results = explode('/', $uri);
        }
        return $results;
    }


    /**
     * @return string
     */
    public static function getHost()
    {
        self::initialize();
        return self::$host;
    }

    public static function getBaseUrl()
    {
        self::initialize();
        return self::$baseUrl;
    }

    /**
     * Load every request parameters depending on the request method
     * used (currently supported : GET, POST, PUT and DELETE). Other method
     * parameters are ignored.
     */
    private static function addParametersFromRequestMethod()
    {
        switch (self::$method) {
            case 'GET':
                self::addParametersFromGlobal($_GET);
                self::addParametersFromGlobal($_FILES);
                break;

            case 'POST':
                self::addParametersFromGlobal($_GET);
                self::addParametersFromGlobal($_POST);
                self::addParametersFromGlobal($_FILES);
                break;

            case 'PUT':
            case 'DELETE':
                self::addParametersFromGlobal($_GET);
                self::addParametersFromGlobal($_FILES);
                parse_str(file_get_contents('php://input'), $paramsSource);
                self::addParametersFromGlobal($paramsSource);
                break;
        }
    }

    /**
     * Add request parameters from a specified associative array (normally a
     * super global such as $_GET and $_POST).
     *
     * @param mixed[] $global
     * @throws \InvalidArgumentException
     */
    private static function addParametersFromGlobal($global)
    {
        if (!is_array($global)) {
            throw new \InvalidArgumentException("Argument must be an array");
        }
        foreach ($global as $name => $value) {
            if (is_string($value)) {
                self::$parameters[$name] = trim($value);
            } elseif (is_array($value)) {
                if (!isset(self::$parameters[$name])) {
                    self::$parameters[$name] = [];
                }
                foreach ($value as $index => $item) {
                    self::$parameters[$name][$index] = $item;
                }
            } else {
                self::$parameters[$name] = $value;
            }
        }
    }
}