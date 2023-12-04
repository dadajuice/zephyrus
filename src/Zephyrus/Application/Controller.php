<?php namespace Zephyrus\Application;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use stdClass;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Response\AbortResponses;
use Zephyrus\Network\Response\DownloadResponses;
use Zephyrus\Network\Response\RenderResponses;
use Zephyrus\Network\Response\StreamResponses;
use Zephyrus\Network\Response\SuccessResponse;
use Zephyrus\Network\Response\XmlResponses;
use Zephyrus\Network\Router\Authorize;
use Zephyrus\Network\Router\Root;
use Zephyrus\Network\Router\RouteDefinition;
use Zephyrus\Network\Router\RouterAttribute;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Security\SecureHeader;

abstract class Controller
{
    protected ?Request $request = null;
    private array $overrideArguments = [];
    private array $restrictedArguments = [];

    use AbortResponses;
    use RenderResponses;
    use StreamResponses;
    use SuccessResponse;
    use XmlResponses;
    use DownloadResponses;

    /**
     * Programmatically defines all routes supported by this controller associated with inner methods. Works parallel
     * with the annotation route definitions.
     */
    public static function initializeRoutes(RouteRepository $repository): void
    {
        static::initializeRoutesFromAttributes($repository);
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
     * Method called immediately after calling the associated route callback method. The default behavior is to inject
     * CSRF token into forms automatically. This should be overridden to customize any operation to be made right after
     * the route callback. This callback receives the previous obtained response from either the before callback or the
     * natural execution. Used as a middleware mechanic.
     *
     * @param Response|null $response
     * @return Response | null
     */
    public function after(?Response $response): ?Response
    {
        if (!is_null($response)
            && $response->getContentType() == ContentType::HTML
            && $this->request->getCsrfGuard()->isEnabled()
            && $this->request->getCsrfGuard()->isHtmlIntegrationEnabled()) {
            $content = $this->request->getCsrfGuard()->injectForms($response->getContent());
            $response->setContent($content);
        }
        $this->request->addToHistory();
        $this->setupSecurityHeaders($response->getSecureHeader());
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
     * Sets the desired security headers for all response of this specific controller.
     *
     * @param SecureHeader $secureHeader
     */
    protected function setupSecurityHeaders(SecureHeader $secureHeader): void
    {
    }

    /**
     * Redirects the user to the previous GET route visited during his session.
     *
     * @return Response
     */
    public function previous(): Response
    {
        return $this->redirectBack($this->request);
    }

    public function getRouteRoot(): string
    {
        return $this->request->getRouteDefinition()->getRouteRoot();
    }

    public function redirectRoot(string $defaultRedirect = "/"): Response
    {
        $root = $this->request->getRouteDefinition()->getRouteRoot();
        if (empty($root)) {
            $root = $defaultRedirect;
        }
        return $this->redirect($root);
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

    private static function initializeBaseRoute(ReflectionClass $class): string
    {
        $classAttributes = $class->getAttributes();
        foreach ($classAttributes as $attribute) {
            if ($attribute->getName() == Root::class) {
                $instance = $attribute->newInstance();
                return rtrim($instance->getBaseRoute(), "/");
            }
        }
        return "";
    }

    private static function initializeBaseAuthorizationRules(ReflectionClass $class): stdClass
    {
        $parentClasses = [];
        $currentClass = $class;
        while ($currentClass = $currentClass->getParentClass()) {
            $parentClasses[] = $currentClass;
        }
        array_unshift($parentClasses, $class);

        $result = (object) [
            'rules' => [],
            'strict' => false
        ];
        for ($i = count($parentClasses) - 1; $i >= 0; $i--) {
            $currentClass = $parentClasses[$i];
            $attributes = $currentClass->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() == Authorize::class) {
                    $instance = $attribute->newInstance();
                    $result = (object) [
                        'rules' => $instance->getRules(),
                        'strict' => $instance->isStrict()
                    ];
                }
            }
        }
        return $result;
    }

    private static function initializeRoutesFromAttributes(RouteRepository $repository): void
    {
        $class = new ReflectionClass(static::class);
        $baseRoute = self::initializeBaseRoute($class);
        $result = self::initializeBaseAuthorizationRules($class);
        $baseRules = $result->rules;
        $strictRules = $result->strict;

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $attributes = $method->getAttributes();
            foreach ($attributes as $attribute) {
                if ($attribute->getName() == Authorize::class) {
                    $instance = $attribute->newInstance();
                    $baseRules = array_merge($baseRules, $instance->getRules());
                    if ($instance->isStrict()) {
                        $strictRules = $instance->isStrict();
                    }
                }
            }
            foreach ($attributes as $attribute) {
                if (in_array($attribute->getName(), RouterAttribute::SUPPORTED_ANNOTATIONS)) {
                    $instance = $attribute->newInstance();
                    $url = $baseRoute . $instance->getRoute();

                    $route = new RouteDefinition($url, $baseRoute);
                    $route->setCallback([static::class, $method->name]);
                    $route->setAcceptedContentTypes($instance->getAcceptedContentTypes());
                    $route->setAuthorizationRules($baseRules, $strictRules);
                    $repository->addRoute($instance->getMethod(), $route);
                }
            }
        }
    }
}
