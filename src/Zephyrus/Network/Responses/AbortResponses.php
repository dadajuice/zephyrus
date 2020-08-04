<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

/**
 * RFC7231 compliant.
 *
 * Trait AbortResponses
 */
trait AbortResponses
{
    public function abort(int $httpStatusCode, string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        $response = new Response($contentType, $httpStatusCode);
        $response->setContent($content);
        return $response;
    }

    public function abortBadRequest(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(400, $content, $contentType);
    }

    public function abortUnauthorized(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(401, $content, $contentType);
    }

    public function abortPaymentRequired(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(402, $content, $contentType);
    }

    public function abortForbidden(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(403, $content, $contentType);
    }

    public function abortNotFound(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(404, $content, $contentType);
    }

    public function abortMethodNotAllowed(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(405, $content, $contentType);
    }

    public function abortNotAcceptable(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(406, $content, $contentType);
    }

    public function abortRequestTimeout(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(408, $content, $contentType);
    }

    public function abortConflict(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(409, $content, $contentType);
    }

    public function abortUnprocessableEntity(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(422, $content, $contentType);
    }

    public function abortInternalError(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(500, $content, $contentType);
    }

    public function abortNotImplemented(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(501, $content, $contentType);
    }

    public function abortBadGateway(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(502, $content, $contentType);
    }

    public function abortServiceUnavailable(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(503, $content, $contentType);
    }

    public function abortGatewayTimeout(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->abort(504, $content, $contentType);
    }
}
