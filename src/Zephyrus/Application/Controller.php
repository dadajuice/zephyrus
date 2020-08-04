<?php namespace Zephyrus\Application;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Responses\AbortResponses;
use Zephyrus\Network\Responses\RenderResponses;
use Zephyrus\Network\Responses\StreamResponses;
use Zephyrus\Network\Responses\SuccessResponse;
use Zephyrus\Network\Responses\XmlResponses;
use Zephyrus\Network\Routable;
use Zephyrus\Network\Router;

abstract class Controller implements Routable
{
    /**
     * @var Router
     */
    private $router;

    /**
     * @var Request;
     */
    protected $request;

    use AbortResponses;
    use RenderResponses;
    use StreamResponses;
    use SuccessResponse;
    use XmlResponses;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->request = &$router->getRequest();
    }

    final protected function get($uri, $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->get($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    final protected function post($uri, $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->post($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    final protected function put($uri, $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->put($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    final protected function patch($uri, $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->patch($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    final protected function delete($uri, $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->delete($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Method called immediately before calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made prior the route callback.
     *
     * @return Response | null
     */
    public function before(): ?Response
    {
        return null;
    }

    /**
     * Method called immediately after calling the associated route callback
     * method. The default behavior is to do nothing. This should be overridden
     * to customize any operation to be made right after the route callback.
     * This callback receives the previous obtained response from either the
     * before callback or the natural execution.
     *
     * @param Response $response
     * @return Response | null
     */
    public function after(?Response $response): ?Response
    {
        return $response;
    }

    /**
     * @return Form
     */
    protected function buildForm(): Form
    {
        $form = new Form();
        $form->addFields($this->request->getParameters());
        return $form;
    }
}
