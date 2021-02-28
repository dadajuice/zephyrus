<?php namespace Zephyrus\Network;

class RequestFactory
{
    public const CUSTOM_METHOD_PARAMETER = '__method';

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
        $uri = self::getCompleteRequestUri($server);
        $method = strtoupper($server['REQUEST_METHOD']);
        $parameters = self::getParametersFromContentType($server['CONTENT_TYPE'] ?? ContentType::PLAIN);
        if (isset($parameters[self::CUSTOM_METHOD_PARAMETER])) {
            $method = strtoupper($parameters[self::CUSTOM_METHOD_PARAMETER]);
        }
        self::$httpRequest = new Request($uri, $method, [
            'parameters' => $parameters,
            'cookies' => $_COOKIE,
            'files' => $_FILES,
            'server' => $server,
            'headers' => (function_exists('getallheaders')) ? getallheaders() : []
        ]);
    }

    /**
     * Load every request parameters depending on the request content type and
     * ensure to properly convert raw data to array parameters.
     *
     * @return array
     */
    private static function getParametersFromContentType(string $contentType): array
    {
        $parameters = array_merge(
            self::getParametersFromGlobal($_GET),
            self::getParametersFromGlobal($_POST),
            self::getParametersFromGlobal($_FILES)
        );
        $paramsSource = [];
        $rawInput = file_get_contents('php://input');
        switch ($contentType) {
            case ContentType::JSON:
                $paramsSource = (array) json_decode($rawInput);
                break;
            case ContentType::XML:
            case ContentType::XML_APP:
                $xml = new \SimpleXMLElement($rawInput);
                $paramsSource = (array) $xml;
                break;
            default:
                parse_str($rawInput, $paramsSource);
        }
        return array_merge($parameters, self::getParametersFromGlobal($paramsSource));
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
                $paramKey = rtrim($name, "[]");
                if (!isset($parameters[$name])) {
                    $parameters[$paramKey] = [];
                }
                foreach ($value as $index => $item) {
                    $parameters[$paramKey][$index] = $item;
                }
            } else {
                $parameters[$name] = $value;
            }
        }
        return $parameters;
    }

    private static function getCompleteRequestUri(array $server): string
    {
        $uri = $server['REQUEST_URI'];
        $isSecure = !empty($server['HTTPS']) && $server['HTTPS'] == 'on';
        $serverProtocol = strtolower($server['SERVER_PROTOCOL']);
        $scheme = substr($serverProtocol, 0, strpos($serverProtocol, '/')) . (($isSecure) ? 's' : '');
        $port = '';
        $host = (isset($server['HTTP_HOST']) ? $server['HTTP_HOST'] : $server['SERVER_NAME']);
        if (strpos($host, ':') === false) {
            $port = $server['SERVER_PORT'];
            $port = ((!$isSecure && $port == '80') || ($isSecure && $port == '443')) ? '' : ':' . $port;
        }
        return $scheme . '://' . $host . $port . $uri;
    }
}
