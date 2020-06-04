<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

/**
 * RFC7231 compliant.
 *
 * Trait AbortResponses
 * @package Zephyrus\Network\Responses
 */
trait AbortResponses
{
    public function buildAbort(int $httpStatusCode, string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        $response = new Response($contentType, $httpStatusCode);
        $response->setContent($content);
        return $response;
    }

    public function buildAbortBadRequest(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->buildAbort(400, $content, $contentType);
    }

    public function buildAbortNotFound(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->buildAbort(404, $content, $contentType);
    }

    public function buildAbortForbidden(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->buildAbort(403, $content, $contentType);
    }

    public function buildAbortMethodNotAllowed(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->buildAbort(405, $content, $contentType);
    }

    public function buildAbortNotAcceptable(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->buildAbort(406, $content, $contentType);
    }

    public function buildAbortInternalError(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return $this->buildAbort(500, $content, $contentType);
    }
}
