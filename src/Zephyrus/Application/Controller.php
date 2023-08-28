<?php namespace Zephyrus\Application;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Responses\AbortResponses;
use Zephyrus\Network\Responses\DownloadResponses;
use Zephyrus\Network\Responses\RenderResponses;
use Zephyrus\Network\Responses\StreamResponses;
use Zephyrus\Network\Responses\SuccessResponse;
use Zephyrus\Network\Responses\XmlResponses;
use Zephyrus\Network\Router\Delete;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\Patch;
use Zephyrus\Network\Router\Post;
use Zephyrus\Network\Router\Put;
use Zephyrus\Network\RouteRepository;

abstract class Controller
{
    protected ?Request $request = null;
    private ?RouteRepository $repository = null;
    private array $overrideArguments = [];
    private array $restrictedArguments = [];

    use AbortResponses;
    use RenderResponses;
    use StreamResponses;
    use SuccessResponse;
    use XmlResponses;
    use DownloadResponses;

    /**
     * Defines all the routes supported by this controller associated with inner methods.
     */
    public function initializeRoutes(): void
    {
    }

    public function initializeRoutesFromAttributes(RouteRepository $repository): void
    {
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $supportedAttributes = [Get::class, Post::class, Put::class, Patch::class, Delete::class];
        foreach ($methods as $method) {
            $attributes = $method->getAttributes();
            foreach ($attributes as $attribute) {
                if (in_array($attribute->getName(), $supportedAttributes)) {
                    $instance = $attribute->newInstance();
                }
                switch ($attribute->getName()) {
                    case Get::class:
                        $repository->get($instance->getRoute(), [$this, $method->name]);
                        break;
                    case Post::class:
                        $repository->post($instance->getRoute(), [$this, $method->name]);
                        break;
                    case Put::class:
                        $repository->put($instance->getRoute(), [$this, $method->name]);
                        break;
                    case Patch::class:
                        $repository->patch($instance->getRoute(), [$this, $method->name]);
                        break;
                    case Delete::class:
                        $repository->delete($instance->getRoute(), [$this, $method->name]);
                        break;
                }
            }
        }
    }

    /**
     * Applies the route collection instance to be used with the inner get, post, put, patch and delete method. These
     * will register the route into the given repository. This method is necessary to make sure we keep the controller
     * instance reference.
     *
     * @param RouteRepository $repository
     * @return void
     */
    public function setRouteRepository(RouteRepository $repository): void
    {
        $this->repository = $repository;
    }

    public function setRequest(Request $request): void
    {
        $this->request = &$request;
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
     * @param Response|null $response
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
    public function overrideArgument(string $argumentName, callable $callback): void
    {
        if (count((new Callback($callback))->getReflection()->getParameters()) != 1) {
            throw new InvalidArgumentException("Override callback should have only one argument which will contain the value of the associated argument name");
        }
        $this->overrideArguments[$argumentName] = $callback;
    }

    /**
     * Applies the given list of rules to a route argument (e.g. {id}) to make sure it is compliant before reaching a
     * method within the controller. Used to ensure argument sanitization. If there's already a set of rules applies
     * for the specified argument name, they will be merged.
     *
     * @param string $parameterName
     * @param Rule[] $rules
     */
    public function restrictArgument(string $parameterName, array $rules): void
    {
        foreach ($rules as $rule) {
            if (!($rule instanceof Rule)) {
                throw new InvalidArgumentException("Specified rules for argument restrictions should be instance of Rule class");
            }
        }
        $this->restrictedArguments[$parameterName] = (isset($this->restrictedArguments[$parameterName]))
            ? array_merge($this->restrictedArguments[$parameterName], $rules)
            : $rules;
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
    public function getOverrideCallbacks(): array
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
    final protected function get(string $uri, string $instanceMethod, string|array $acceptedFormats = ContentType::ANY): void
    {
        if (is_null($this->repository)) {
            throw new RuntimeException("You must first set a RouteRepository instance on which the route definition will apply. Be sure to use the setRouteRepository method before any calls to get, post, put, patch and delete methods.");
        }
        $this->repository->get($uri, [$this, $instanceMethod], $acceptedFormats);
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
    final protected function post(string $uri, string $instanceMethod, string|array $acceptedFormats = ContentType::ANY): void
    {
        if (is_null($this->repository)) {
            throw new RuntimeException("You must first set a RouteRepository instance on which the route definition will apply. Be sure to use the setRouteRepository method before any calls to get, post, put, patch and delete methods.");
        }
        $this->repository->post($uri, [$this, $instanceMethod], $acceptedFormats);
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
    final protected function put(string $uri, string $instanceMethod, string|array $acceptedFormats = ContentType::ANY): void
    {
        if (is_null($this->repository)) {
            throw new RuntimeException("You must first set a RouteRepository instance on which the route definition will apply. Be sure to use the setRouteRepository method before any calls to get, post, put, patch and delete methods.");
        }
        $this->repository->put($uri, [$this, $instanceMethod], $acceptedFormats);
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
    final protected function patch(string $uri, string $instanceMethod, string|array $acceptedFormats = ContentType::ANY): void
    {
        if (is_null($this->repository)) {
            throw new RuntimeException("You must first set a RouteRepository instance on which the route definition will apply. Be sure to use the setRouteRepository method before any calls to get, post, put, patch and delete methods.");
        }
        $this->repository->patch($uri, [$this, $instanceMethod], $acceptedFormats);
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
    final protected function delete(string $uri, string $instanceMethod, string|array $acceptedFormats = ContentType::ANY): void
    {
        if (is_null($this->repository)) {
            throw new RuntimeException("You must first set a RouteRepository instance on which the route definition will apply. Be sure to use the setRouteRepository method before any calls to get, post, put, patch and delete methods.");
        }
        $this->repository->delete($uri, [$this, $instanceMethod], $acceptedFormats);
    }

    /**
     * Builds a Form class instance automatically filled with all the request body parameters. Should be used to add
     * validation rules.
     *
     * @param bool $includeRouteArguments
     * @return Form
     */
    protected function buildForm(bool $includeRouteArguments = false): Form
    {
        $form = new Form();
        $parameters = $this->request->getParameters();
        if ($includeRouteArguments) {
            $parameters = array_merge($this->request->getArguments(), $parameters);
        }
        $form->addFields($parameters);
        $form->addFields($this->request->getFiles());
        return $form;
    }
}
