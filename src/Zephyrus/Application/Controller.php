<?php namespace Zephyrus\Application;

use Zephyrus\Exceptions\RouteArgumentException;
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
     * @var Request;
     */
    protected $request;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var array
     */
    private $overrideArguments = [];

    /**
     * @var array
     */
    private $restrictedArguments = [];

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

    /**
     * Method called immediately before calling the associated route callback method. The default behavior is to do
     * nothing. This should be overridden to customize any operation to be made prior the route callback. Used as a
     * middleware mechanic.
     *
     * @return Response | null
     */
    public function before(): ?Response
    {
        return null;
    }

    /**
     * Method called immediately after calling the associated route callback method. The default behavior is to do
     * nothing. This should be overridden to customize any operation to be made right after the route callback. This
     * callback receives the previous obtained response from either the before callback or the natural execution. Used
     * as a middleware mechanic.
     *
     * @param Response $response
     * @return Response | null
     */
    public function after(?Response $response): ?Response
    {
        return $response;
    }

    /**
     * Modifies a specified route argument value (e.g. {user}) according to the given callback. The callback should
     * have one parameter that will be the actual argument value before doing the modification. If there's already an
     * override defined for the specified argument name, it will be overwritten.
     *
     * @param string $argumentName
     * @param callable $callback
     */
    public function overrideArgument(string $argumentName, callable $callback)
    {
        if (count((new Callback($callback))->getReflection()->getParameters()) != 1) {
            throw new \InvalidArgumentException("Override callback should have only one argument which will contain the value of the associated argument name");
        }
        $this->overrideArguments[$argumentName] = $callback;
    }

    /**
     * Applies the given list of rules to a route argument (e.g. {id}) to make sure it is compliant before reaching a
     * method within the controller. Used to ensure argument sanitization. If there's already a set of rules applies
     * for the specified argument name, they will be overwritten.
     *
     * @param string $parameterName
     * @param Rule[] $rules
     */
    public function restrictArgument(string $parameterName, array $rules)
    {
        foreach ($rules as $rule) {
            if (!($rule instanceof Rule)) {
                throw new \InvalidArgumentException("Specified rules for argument restrictions should be instance of Rule class");
            }
        }
        $this->restrictedArguments[$parameterName] = $rules;
    }

    /**
     * @return array
     */
    public function getRestrictedArguments(): array
    {
        return $this->restrictedArguments;
    }

    /**
     * @return array
     */
    public function getOverriddenArguments(): array
    {
        return $this->overrideArguments;
    }

    /**
     * Provides a way to handle exception thrown if a route argument doesn't match the defined rules before reaching the
     * main script (index). Default behavior is to throw the received exception to the caller. Should be redefined to
     * add application logic (send a generic error response, redirect to another url, log error, etc.) and thus always
     * return a proper Response or simply throw the exception.
     *
     * @param RouteArgumentException $exception
     * @throws RouteArgumentException
     * @return Response
     */
    public function handleRouteArgumentException(RouteArgumentException $exception): Response
    {
        throw $exception;
    }

    /**
     * Adds a new GET route for the application. The GET method must be used to represent a specific resource (or
     * collection) in some representational format (HTML, JSON, XML, ...). Normally, a GET request must only present
     * data and not alter them in any way.
     *
     * E.g. GET /books
     *      GET /book/{id}
     *
     * @param string $uri
     * @param string $instanceMethod
     * @param string | array $acceptedFormats
     */
    final protected function get(string $uri, string $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->get($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Adds a new POST route for the application. The POST method must be used to create a new entry in a collection. It
     * is rarely used on a specific resource.
     *
     * E.g. POST /books
     *
     * @param string $uri
     * @param string $instanceMethod
     * @param string | array $acceptedFormats
     */
    final protected function post(string $uri, string $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->post($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Adds a new PUT route for the application. The PUT method must be used to update a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. PUT /book/{id}
     *
     * @param string $uri
     * @param string $instanceMethod
     * @param string | array $acceptedFormats
     */
    final protected function put(string $uri, string $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->put($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Adds a new PATCH route for the application. The PATCH method must be used to update a specific resource or
     * collection and must be considered idempotent. Should be used instead of PUT when it is possible to update only
     * given fields to update and not the entire resource.
     *
     * E.g. PATCH /book/{id}
     *
     * @param string $uri
     * @param string $instanceMethod
     * @param string | array $acceptedFormats
     */
    final protected function patch(string $uri, string $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->patch($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Adds a new DELETE route for the application. The DELETE method must be used only to delete a specific resource or
     * collection and must be considered idempotent.
     *
     * E.g. DELETE /book/{id}
     *      DELETE /books
     *
     * @param string $uri
     * @param string $instanceMethod
     * @param string | array $acceptedFormats
     */
    final protected function delete(string $uri, string $instanceMethod, $acceptedFormats = ContentType::ANY)
    {
        $this->router->delete($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Builds a Form class instance automatically filled with all the request parameters. Should be used to add
     * validations.
     *
     * @return Form
     */
    protected function buildForm(): Form
    {
        $form = new Form();
        $form->addFields($this->request->getParameters());
        return $form;
    }
}
