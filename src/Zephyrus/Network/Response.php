<?php namespace Zephyrus\Network;

class Response
{
    private static $responseCodeSent = false;
    private static $responseContentTypeSent = false;

    /**
     * Send the HTTP response code.
     *
     * @param int $responseCode
     */
    public static function sendResponseCode($responseCode = 200)
    {
        if (self::$responseCodeSent) {
            throw new \RuntimeException("HTTP response status code already sent");
        }
        http_response_code($responseCode);
        self::$responseCodeSent = true;
    }

    /**
     * @param string $name
     * @param string $content
     */
    public static function sendHeader($name, $content)
    {
        header("$name:$content");
    }

    /**
     * @param string $contentType
     * @param string $charset
     */
    public static function sendContentType($contentType = ContentType::HTML, $charset = 'UTF-8')
    {
        if (self::$responseContentTypeSent) {
            throw new \RuntimeException("HTTP Content-Type header already sent");
        }
        header('Content-Type: ' . $contentType . ';charset=' . $charset);
        self::$responseContentTypeSent = true;
    }

    public static function abort($httpStatusCode)
    {
        self::sendResponseCode($httpStatusCode);
        self::sendContentType();
        exit;
    }

    public static function abortNotFound()
    {
        self::abort(404);
    }

    public static function abortInternalError()
    {
        self::abort(500);
    }

    public static function abortForbidden()
    {
        self::abort(403);
    }

    public static function abortMethodNotAllowed()
    {
        self::abort(405);
    }

    public static function abortNotAcceptable()
    {
        self::abort(406);
    }
}
