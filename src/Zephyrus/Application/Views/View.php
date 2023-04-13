<?php namespace Zephyrus\Application\Views;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

abstract class View
{
    private string $page;
    private string $path;

    abstract public function render(array $args = []): Response;

    abstract protected function buildPathFromPage(string $pageToRender): string;

    public function __construct(string $pageToRender)
    {
        $this->page = $pageToRender;
        $this->path = $this->buildPathFromPage($pageToRender);
    }

    /**
     * Verifies if the path match an existing PHP file.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return file_exists($this->path) && is_readable($this->path);
    }

    /**
     * Retrieves the given PHP page name.
     *
     * @return string
     */
    public function getPage(): string
    {
        return $this->page;
    }

    /**
     * Retrieves the complete path to the specified PHP view file.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    protected function buildResponse(string $content): Response
    {
        $response = new Response(ContentType::HTML, 200);
        $response->setContent($content);
        return $response;
    }
}
