<?php namespace Zephyrus\Network;

use Zephyrus\Application\Callback;

class Response
{
    /**
     * @var string
     */
    private $content;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $contentType;

    /**
     * @var string
     */
    private $charset;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var callable
     */
    private $contentCallback = null;

    /**
     * Builds a response with the given content type and response code (defaults
     * text/html 200 OK). Charset is by default UTF-8. Can be modified with the
     * setContentType() method prior calling the send() method. Standard response
     * types should be constructed from the ResponseFactory class for easier and
     * best results.
     *
     * @param string $contentType
     * @param int $code
     */
    public function __construct($contentType = ContentType::HTML, $code = 200)
    {
        $this->contentType = $contentType;
        $this->code = $code;
        $this->charset = 'UTF-8';
    }

    /**
     * Sends the complete response to the client (headers and content). From
     * that point it should have no more data sent (e.g. echoes).
     */
    public function send()
    {
        http_response_code($this->code);
        header('Content-Type: ' . $this->contentType . ';charset=' . $this->charset);
        foreach ($this->headers as $name => $content) {
            header("$name:$content");
        }
        $this->sendContent();
    }

    /**
     * Sends only the content to the client (useful for SSE streaming). Headers
     * must be manually sent before calling this method.
     */
    public function sendContent()
    {
        if (!is_null($this->contentCallback)) {
            $callback = new Callback($this->contentCallback);
            $callback->execute();
        }
        echo $this->content;
    }

    /**
     * Inserts a single header to the response. Can also overrides existing
     * header having the same name.
     *
     * @param string $name
     * @param string $content
     */
    public function addHeader(string $name, string $content)
    {
        $this->headers[$name] = $content;
    }

    /**
     * Inserts an associative array of headers (name => content) to the
     * response. Can also overrides existing header having the same
     * name.
     *
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $content) {
            $this->headers[$name] = $content;
        }
    }

    /**
     * Makes sure the response's content type is text/html and that the content contains at least the <html> tag.
     *
     * @return bool
     */
    public function hasHtmlContent(): bool
    {
        return $this->contentType == ContentType::HTML
            && str_contains($this->content, "<html>");
    }

    /**
     * Applies a given callback to be executed for preparing content (useful
     * for SSE streaming).
     *
     * @param callable $contentCallback
     */
    public function setContentCallback($contentCallback)
    {
        $this->contentCallback = $contentCallback;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content)
    {
        $this->content = $content;
    }

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset)
    {
        $this->charset = $charset;
    }
}
