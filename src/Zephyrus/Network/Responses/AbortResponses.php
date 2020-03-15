<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait AbortResponses
{
    public function buildAbort(int $httpStatusCode): Response
    {
        return new Response(ContentType::PLAIN, $httpStatusCode);
    }

    public function buildAbortNotFound(): Response
    {
        return new Response(ContentType::PLAIN, 404);
    }

    public function buildAbortInternalError(): Response
    {
        return new Response(ContentType::PLAIN, 500);
    }

    public function buildAbortForbidden(): Response
    {
        return new Response(ContentType::PLAIN, 403);
    }

    public function buildAbortMethodNotAllowed(): Response
    {
        return new Response(ContentType::PLAIN, 405);
    }

    public function buildAbortNotAcceptable(): Response
    {
        return new Response(ContentType::PLAIN, 406);
    }
}
