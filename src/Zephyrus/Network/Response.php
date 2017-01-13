<?php namespace Zephyrus\Network;

use Zephyrus\Exceptions\NetworkException;

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

    /**
     * @param int $httpStatusCode
     * @throws NetworkException
     */
    public function abort(int $httpStatusCode)
    {
        throw new NetworkException($httpStatusCode);
    }

    public function abortNotFound()
    {
        throw new NetworkException(404);
    }

    public function abortInternalError()
    {
        throw new NetworkException(500);
    }

    public function abortForbidden()
    {
        throw new NetworkException(403);
    }

    public function abortMethodNotAllowed()
    {
        throw new NetworkException(405);
    }

    public function abortNotAcceptable()
    {
        throw new NetworkException(406);
    }
}
