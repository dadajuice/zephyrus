<?php namespace Zephyrus\Network;

class Response
{
    private $responseCodeSent = false;
    private $responseContentTypeSent = false;

    /**
     * Send the HTTP response code.
     *
     * @param int $responseCode
     */
    public function sendResponseCode($responseCode = 200)
    {
        if ($this->responseCodeSent) {
            throw new \RuntimeException("HTTP response status code already sent");
        }
        http_response_code($responseCode);
        $this->responseCodeSent = true;
    }

    /**
     * @param string $name
     * @param string $content
     */
    public function sendHeader($name, $content)
    {
        header("$name:$content");
    }

    /**
     * @param string $contentType
     * @param string $charset
     */
    public function sendContentType($contentType = ContentType::HTML, $charset = 'UTF-8')
    {
        if ($this->responseContentTypeSent) {
            throw new \RuntimeException("HTTP Content-Type header already sent");
        }
        header('Content-Type: ' . $contentType . ';charset=' . $charset);
        $this->responseContentTypeSent = true;
    }

    public function abort($httpStatusCode)
    {
        $this->sendResponseCode($httpStatusCode);
        $this->sendContentType();
        exit;
    }

    public function abortNotFound()
    {
        self::abort(404);
    }

    public function abortInternalError()
    {
        self::abort(500);
    }

    public function abortForbidden()
    {
        self::abort(403);
    }

    public function abortMethodNotAllowed()
    {
        self::abort(405);
    }

    public function abortNotAcceptable()
    {
        self::abort(406);
    }
}
