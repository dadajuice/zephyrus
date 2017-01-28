<?php namespace Zephyrus\Network;

class RequestFactory
{
    /**
     * @var Request
     */
    private static $httpRequest = null;

    /**
     * Creates a Request object using the client HTTP request data.
     *
     * @return Request
     */
    public static function read(): Request
    {
        if (is_null(self::$httpRequest)) {
            self::captureHttpRequest();
        }
        return self::$httpRequest;
    }

    /**
     * Applies a custom request instance to be stored in the factory accessible
     * with the read method.
     *
     * @param Request $request
     */
    public static function set(?Request $request)
    {
        self::$httpRequest = $request;
    }

    /**
     * Reads the HTTP data to build corresponding request instance.
     */
    private static function captureHttpRequest()
    {
        $server = $_SERVER;
        $uri = $server['REQUEST_URI'];
        $method = strtoupper($server['REQUEST_METHOD']);
        $server['REMOTE_ADDR'] = getenv('HTTP_X_FORWARDED_FOR') ?? getenv('REMOTE_ADDR');
        $parameters = self::getParametersFromMethod($method);
        if (isset($parameters['__method'])) {
            $method = strtoupper($parameters['__method']);
        }
        self::$httpRequest = new Request($uri, $method, $parameters, $_COOKIE, $_FILES, $server);
    }

    /**
     * Load every request parameters depending on the request method
     * used (currently supported : GET, POST, PUT and DELETE). Other method
     * parameters are ignored.
     *
     * @param $method
     * @return array
     */
    private static function getParametersFromMethod(string $method): array
    {
        $parameters = array_merge(self::getParametersFromGlobal($_GET), self::getParametersFromGlobal($_FILES));
        switch ($method) {
            case 'POST':
                return array_merge($parameters, self::getParametersFromGlobal($_POST));
            case 'PUT':
            case 'DELETE':
                parse_str(file_get_contents('php://input'), $paramsSource);
                return array_merge($parameters, self::getParametersFromGlobal($paramsSource));
        }
        return $parameters;
    }

    /**
     * Add request parameters from a specified associative array (normally a
     * super global such as $_GET and $_POST).
     *
     * @param mixed[] $global
     * @return array
     */
    private static function getParametersFromGlobal(array $global): array
    {
        $parameters = [];
        foreach ($global as $name => $value) {
            if (is_string($value)) {
                $parameters[$name] = trim($value);
            } elseif (is_array($value)) {
                if (!isset($parameters[$name])) {
                    $parameters[$name] = [];
                }
                foreach ($value as $index => $item) {
                    $parameters[$name][$index] = $item;
                }
            } else {
                $parameters[$name] = $value;
            }
        }
        return $parameters;
    }
}
