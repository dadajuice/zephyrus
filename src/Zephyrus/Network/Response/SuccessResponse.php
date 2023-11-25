<?php namespace Zephyrus\Network\Response;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;

trait SuccessResponse
{
    /**
     * Renders the given data as json string.
     *
     * @param mixed $data
     * @return Response
     */
    public function json(mixed $data): Response
    {
        $response = new Response(ContentType::JSON, 200);
        $response->setContent(json_encode($data));
        return $response;
    }

    /**
     * Renders the given data as plain string.
     *
     * @param string $data
     * @return Response
     */
    public function plain(string $data): Response
    {
        $response = new Response(ContentType::PLAIN, 200);
        $response->setContent($data);
        return $response;
    }

    /**
     * Servers the data as text/css to the client. Useful for routes which generate dynamic CSS or minified CSS.
     *
     * @param string $data
     * @return Response
     */
    public function css(string $data): Response
    {
        $response = new Response(ContentType::CSS, 200);
        $response->setContent($data);
        return $response;
    }

    /**
     * Servers the data as text/javascript to the client. Useful for routes which generate dynamic JS or minified JS.
     *
     * @param string $data
     * @return Response
     */
    public function js(string $data): Response
    {
        $response = new Response(ContentType::JAVASCRIPT, 200);
        $response->setContent($data);
        return $response;
    }

    /**
     * Throws an HTTP "201 Created" header that should be used with api compliant post response. Needs a redirect
     * url (will send the location header just like a regular redirection). Optionally, can include a content body (e.g.
     * JSON response of the created element).
     *
     * @param string $redirectUrl
     * @param string $content
     * @param string $contentType
     * @return Response
     */
    public function created(string $redirectUrl, string $content = "", string $contentType = ContentType::PLAIN): Response
    {
        $response = new Response($contentType, 201);
        $response->setContent($content);
        $response->addHeader('Location', $redirectUrl);
        return $response;
    }

    /**
     * Creates a simple no content response (204) that should be sent in response to a PUT request.
     *
     * @return Response
     */
    public function noContent(): Response
    {
        return new Response(ContentType::PLAIN, 204);
    }

    /**
     * Redirect user to specified URL. Throws an HTTP "303 See Other" header instead of the default 301. This indicates,
     * more precisely, that the response is elsewhere.
     *
     * @param string $url
     * @return Response
     */
    public function redirect(string $url): Response
    {
        $response = new Response(ContentType::PLAIN, 303);
        $response->addHeader('Location', $url);
        return $response;
    }

    /**
     * Redirects the user to the previous GET route visited during his session.
     *
     * @param Request $request
     * @return Response
     */
    public function redirectBack(Request $request): Response
    {
        return $this->redirect($request->getReferer());
    }
}
