<?php namespace Zephyrus\Application;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\ResponseFactory;
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

    /**
     * Renders the specified Pug view with corresponding arguments. If a pager
     * is to be shown in the page, it must be given.
     *
     * @param string $page
     * @param array $args
     * @return Response
     */
    protected function render($page, $args = []): Response
    {
        return ResponseFactory::getInstance()->buildView($page, $args);
    }

    /**
     * Renders the given data as HTML. Default behavior of any direct input.
     *
     * @param string $data
     * @return Response
     */
    protected function html(string $data): Response
    {
        return ResponseFactory::getInstance()->buildHtml($data);
    }

    /**
     * Renders the given data as json string.
     *
     * @param mixed $data
     * @return Response
     */
    protected function json($data): Response
    {
        return ResponseFactory::getInstance()->buildJson($data);
    }

    /**
     * Renders the given data as plain string.
     *
     * @param mixed $data
     * @return Response
     */
    protected function plain($data): Response
    {
        return ResponseFactory::getInstance()->buildPlain($data);
    }

    /**
     * Renders a given file as a downloadable content with application/octet-stream
     * content type. If no filename is given, it will automatically use the actual
     * file basename. If the deleteAfter argument is set to true, it will
     * automatically remove the file after sending it. Useful for temporary files.
     *
     * @param string $filePath
     * @param null|string $filename
     * @param bool $deleteAfter
     * @return Response
     */
    protected function download(string $filePath, ?string $filename = null, bool $deleteAfter = false): Response
    {
        return ResponseFactory::getInstance()->buildDownload($filePath, $filename, $deleteAfter);
    }

    /**
     * Does a simple server-sent event response which will do a simple polling.
     *
     * @param mixed $data
     * @param string $eventId
     * @param int $retry
     * @return Response
     */
    protected function ssePolling($data, $eventId = 'stream', $retry = 1000): Response
    {
        return ResponseFactory::getInstance()->buildPollingSse($data, $eventId, $retry);
    }

    /**
     * Does a streaming server-sent event response which will loop and execute
     * the specified callback indefinitely and update the client only when
     * needed.
     *
     * @param $callback
     * @param string $eventId
     * @param int $retry
     * @return Response
     */
    protected function sseStreaming($callback, $eventId = 'stream', $sleep = 1): Response
    {
        return ResponseFactory::getInstance()->buildStreamingSse($callback, $eventId, $sleep);
    }

    /**
     * Used to implement a manual SSE flow (e.g. progressbar). Requires a callback
     * which receives a specific function destined to be used when sending an SSE
     * message to the client side.
     *
     * @param $callback
     * @return Response
     */
    protected function sseFlow($callback): Response
    {
        return ResponseFactory::getInstance()->buildFlowSse($callback);
    }

    /**
     * Renders the given data as XML. The data can be a direct SimpleXMLElement
     * or simply an associative array. If an array is provided, the root
     * element must be explicitly given.
     *
     * @param array | \SimpleXMLElement $data
     * @param string $root
     * @return Response
     */
    protected function xml($data, $root = ""): Response
    {
        return ResponseFactory::getInstance()->buildXml($data, $root);
    }

    /**
     * Redirect user to specified URL. Throws an HTTP "303 See Other" header
     * instead of the default 301. This indicates, more precisely, that the
     * response if elsewhere.
     *
     * @param string $url
     * @return Response
     */
    public function redirect(string $url): Response
    {
        return ResponseFactory::getInstance()->buildRedirect($url);
    }

    /**
     * Throws an HTTP "201 Created" header that should be used with api compliant
     * post response. Needs a redirect url (will send the location header just like
     * a regular redirection). Optionally, can include a content body (e.g. JSON
     * response of the created element).
     *
     * @param string $redirectUrl
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    public function created(string $redirectUrl, string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildCreated($redirectUrl, $content, $contentType);
    }

    /**
     * @param int $httpStatusCode
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abort(int $httpStatusCode, string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbort($httpStatusCode, $content, $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abortNotFound(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbortNotFound($content, $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abortBadRequest(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbortBadRequest($content, $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abortInternalError(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbortInternalError($content, $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abortForbidden(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbortForbidden($content, $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abortMethodNotAllowed(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbortMethodNotAllowed($content, $contentType);
    }

    /**
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    protected function abortNotAcceptable(string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        return ResponseFactory::getInstance()->buildAbortNotAcceptable($content, $contentType);
    }
}
