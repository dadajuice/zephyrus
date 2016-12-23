<?php namespace Zephyrus\Network;

class Response
{
    /**
     * Send the HTTP response code.
     *
     * @param int $responseCode
     */
    public static function sendResponseCode($responseCode = 200)
    {
        http_response_code($responseCode);
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
        header('Content-Type: ' . $contentType . ';charset=' . $charset);
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