<?php namespace Zephyrus\Network;

use Zephyrus\Security\SecureHeader;

class Response
{
    private SecureHeader $secureHeader;
    private string $content = "";
    private int $code;
    private string $contentType;
    private string $charset;
    private array $headers = [];

    /**
     * @var callable
     */
    private $contentCallback = null;

    public static function builder(): ResponseBuilder
    {
        return new ResponseBuilder();
    }

    /**
     * Builds a response with the given content type and response code (defaults text/html 200 OK). Charset is by
     * default UTF-8. Can be modified with the setContentType() method prior calling the send() method. Standard
     * response types should be constructed from the Response static calls for easier and best results.
     *
     * @param string $contentType
     * @param int $code
     */
    public function __construct(string $contentType = ContentType::HTML, int $code = 200)
    {
        $this->contentType = $contentType;
        $this->code = $code;
        $this->charset = "UTF-8";
        $this->secureHeader = new SecureHeader();
    }

    /**
     * Sends the complete response to the client (headers and content). From that point it should have no more data
     * sent (e.g. echoes).
     */
    public function send(): void
    {
        http_response_code($this->code);
        header('Content-Type: ' . $this->contentType . ';charset=' . $this->charset);
        foreach ($this->headers as $name => $content) {
            header("$name:$content");
        }
        $this->secureHeader->send();
        $this->sendContent();
    }

    /**
     * Sends only the content to the client (useful for SSE streaming). Headers must be manually sent before calling
     * this method.
     */
    public function sendContent(): void
    {
        if (!is_null($this->contentCallback)) {
            ($this->contentCallback)();
        }
        echo $this->content;
    }

    /**
     * Inserts a single header to the response. Can also override existing header having the same name.
     *
     * @param string $name
     * @param string $content
     */
    public function addHeader(string $name, string $content): void
    {
        $this->headers[$name] = $content;
    }

    /**
     * Inserts an associative array of headers (name => content) to the response. Can also override existing header
     * having the same name.
     *
     * @param array $headers
     */
    public function addHeaders(array $headers): void
    {
        foreach ($headers as $name => $content) {
            $this->headers[$name] = $content;
        }
    }

    /**
     * Makes sure the response's content type is text/html and that the content contains at least the <html> tag.
     *
     * @return bool
     * @noinspection HtmlRequiredLangAttribute
     */
    public function hasHtmlContent(): bool
    {
        return $this->contentType == ContentType::HTML && str_contains($this->content, "<html>");
    }

    /**
     * Applies a given callback to be executed for preparing content (useful for SSE streaming).
     *
     * @param callable $contentCallback
     */
    public function setContentCallback(callable $contentCallback): void
    {
        $this->contentCallback = $contentCallback;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    public function getSecureHeader(): SecureHeader
    {
        return $this->secureHeader;
    }
}
