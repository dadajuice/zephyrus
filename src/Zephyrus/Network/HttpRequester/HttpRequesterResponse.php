<?php namespace Zephyrus\Network\HttpRequester;

class HttpRequesterResponse
{
    /**
     * Contains the received raw string response. Can then be parse if needed (e.g. JSON response).
     *
     * @var string
     */
    private string $rawResponse;

    /**
     * The content type associated with the received response (determine how the response should be interpreted).
     *
     * @var string|null
     */
    private ?string $contentType;

    /**
     * Received HTTP Code (e.g. 200).
     *
     * @var int
     */
    private int $httpCode;

    /**
     * Received response headers.
     *
     * @var array
     */
    private array $headers;

    /**
     * Holds the raw results of the curl_getinfo method call.
     *
     * @var array
     */
    private array $information;

    public function __construct(string $rawResponse, array $information, array $headers = [])
    {
        $this->rawResponse = $rawResponse;
        $this->information = $information;
        $this->headers = $headers;
        $this->initialize();
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getResponse(): string
    {
        return $this->rawResponse;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Retrieves all response information gather by cURL after the request execution.
     *
     * @see https://www.php.net/manual/fr/function.curl-getinfo.php
     * @return array
     */
    public function getInformation(): array
    {
        return $this->information;
    }

    private function initialize()
    {
        $this->contentType = $this->information['content_type'];
        $this->httpCode = $this->information['http_code'];
    }
}
