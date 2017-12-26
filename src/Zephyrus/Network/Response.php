<?php namespace Zephyrus\Network;

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

    public function __construct($contentType = ContentType::HTML, $code = 200)
    {
        $this->contentType = $contentType;
        $this->code = $code;
        $this->charset = 'UTF-8';
    }

    public function send()
    {
        http_response_code($this->code);
        header('Content-Type: ' . $this->contentType . ';charset=' . $this->charset);
        foreach ($this->headers as $name => $content) {
            header("$name:$content");
        }
        $this->sendContent();
    }

    public function sendContent()
    {
        echo $this->content;
    }

    public function addHeader(string $name, string $content)
    {
        $this->headers[$name] = $content;
    }

    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $content) {
            $this->headers[$name] = $content;
        }
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
     * @param string $charset
     */
    public function setCharset(string $charset)
    {
        $this->charset = $charset;
    }
}
