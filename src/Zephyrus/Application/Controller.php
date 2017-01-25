<?php namespace Zephyrus\Application;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Network\Response;
use Zephyrus\Network\Routable;
use Zephyrus\Network\Router;
use Zephyrus\Utilities\Pager;

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

    /**
     * @var Response
     */
    protected $response;

    public function __construct(Router $router)
    {
        $this->router = $router;
        $this->request = RequestFactory::read();
        $this->response = new Response();
    }

    protected function get($uri, $instanceMethod, $acceptedFormats = null)
    {
        $this->router->get($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    protected function post($uri, $instanceMethod, $acceptedFormats = null)
    {
        $this->router->post($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    protected function put($uri, $instanceMethod, $acceptedFormats = null)
    {
        $this->router->put($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    protected function delete($uri, $instanceMethod, $acceptedFormats = null)
    {
        $this->router->delete($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Renders the specified Pug view with corresponding arguments. If a pager
     * is to be shown in the page, it must be given.
     *
     * @param string $page
     * @param array $args
     * @param Pager|null $pager
     */
    protected function render($page, $args = [], Pager $pager = null)
    {
        $view = ViewBuilder::getInstance()->build($page);
        if (!is_null($pager)) {
            $view->setPager($pager);
        }
        echo $view->render($args);
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

    /**
     * Renders the given data as HTML. Default behavior of any direct input.
     *
     * @param string $data
     */
    protected function html($data)
    {
        $this->response->sendResponseCode();
        $this->response->sendContentType(ContentType::HTML);
        echo $data;
    }

    /**
     * Renders the given data as json string.
     *
     * @param string $data
     */
    protected function json($data)
    {
        $this->response->sendResponseCode();
        $this->response->sendContentType(ContentType::JSON);
        echo json_encode($data);
    }

    /**
     * Does a server-sent event response.
     *
     * @param string $data
     * @param int $eventId
     * @param int $retry
     */
    protected function sse($data, $eventId = 0, $retry = 1000)
    {
        $this->response->sendResponseCode();
        $this->response->sendContentType(ContentType::SSE);
        $this->response->sendHeader('Cache-Control', 'no-cache');
        echo "id: $eventId" . PHP_EOL;
        echo "retry: " . $retry . PHP_EOL;
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    /**
     * Renders the given data as XML. The data can be a direct SimpleXMLElement
     * or simply an associative array. If an array is provided, the root
     * element must be explicitly given.
     *
     * @param array | \SimpleXMLElement $data
     * @param string $root
     */
    protected function xml($data, $root = "")
    {
        $this->response->sendResponseCode();
        $this->response->sendContentType(ContentType::XML);
        if ((!$data instanceof \SimpleXMLElement) && !is_array($data)) {
            throw new \RuntimeException("Cannot parse specified data as XML");
        }

        if ($data instanceof \SimpleXMLElement) {
            echo $data->asXML();
        }
        if (is_array($data)) {
            $xml = new \SimpleXMLElement('<' . $root . '/>');
            array_walk_recursive($data, array ($xml, 'addChild'));
            echo $xml->asXML();
        }
    }
}
