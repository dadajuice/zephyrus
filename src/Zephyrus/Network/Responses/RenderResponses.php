<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Application\Views\PhpView;
use Zephyrus\Application\Views\PugEngine;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait RenderResponses
{
    private ?PugEngine $pugEngine = null;

    /**
     * Renders the specified view with corresponding arguments using the configured rendering engine. By default, the
     * PugEngine is built if none specific is given.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    public function render(string $page, array $args = []): Response
    {
        if (is_null($this->pugEngine)) {
            $this->pugEngine = new PugEngine();
        }
        return $this->pugEngine->buildView($page)->render($args);
    }

    /**
     * Renders the specified PHP view with corresponding arguments.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    public function renderPhp(string $page, array $args = []): Response
    {
        $view = new PhpView($page);
        return $view->render($args);
    }

    /**
     * Renders the given data as HTML. Default behavior of any direct input.
     *
     * @param string $data
     * @return Response
     */
    public function html(string $data): Response
    {
        $response = new Response(ContentType::HTML, 200);
        $response->setContent($data);
        return $response;
    }

    /**
     * Applies the rendering engine that should be used when calling the "render" method. As of now, only Pug is
     * supported.
     *
     * @param PugEngine $engine
     */
    public function setRenderEngine(PugEngine $engine): void
    {
        $this->pugEngine = $engine;
    }
}
