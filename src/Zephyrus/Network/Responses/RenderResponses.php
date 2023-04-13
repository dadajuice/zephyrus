<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Application\Views\PhpView;
use Zephyrus\Application\Views\PugView;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait RenderResponses
{
    /**
     * Renders the specified Pug view with corresponding arguments.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    public function render(string $page, array $args = []): Response
    {
        $view = new PugView($page);
        return $view->render($args);
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
}
