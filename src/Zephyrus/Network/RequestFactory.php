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
    public static function create(): Request
    {
        if (is_null(self::$httpRequest)) {
            $server = $_SERVER;
            $uri = $server['REQUEST_URI'];
            $method = strtoupper($server['REQUEST_METHOD']);
            $server['REMOTE_ADDR'] = (getenv('HTTP_X_FORWARDED_FOR'))
                ? getenv('HTTP_X_FORWARDED_FOR')
                : getenv('REMOTE_ADDR');
            $parameters = self::getParametersFromMethod($method);
            self::$httpRequest = new Request($uri, $method, $parameters, $_COOKIE, $_FILES, $server);
        }
        return self::$httpRequest;
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
        $parameters = self::getParametersFromGlobal($_GET);
        switch ($method) {
            case 'GET':
                return array_merge($parameters, self::getParametersFromGlobal($_FILES));
            case 'POST':
                return array_merge($parameters, self::getParametersFromGlobal($_POST), self::getParametersFromGlobal($_FILES));
            case 'PUT':
            case 'DELETE':
                parse_str(file_get_contents('php://input'), $paramsSource);
                $parameters = array_merge($parameters, self::getParametersFromGlobal($_FILES));
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
