<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Rule;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\Request;

class ControllerRestrictedParameterTest extends TestCase
{
    public function testSuccessfulRestrictedArguments()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
            }

            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/4', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('4', $output);
    }

    public function testSuccessfulRestrictedArgumentsMerge()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
                parent::restrictArgument('id', [Rule::range(1, 10, "Invalid route range")]);
            }

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                return $this->plain("failed!" . $exception->getRuleId());
            }

            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/40', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('failed!1', $output);
    }

    public function testSuccessfulMultipleRestrictedArguments()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{date}', 'read');
                parent::restrictArgument('date', [
                    Rule::date("Invalid route argument"),
                    Rule::dateBefore('2020-05-01', "Date too far")
                ]);
            }

            public function read($date)
            {
                return $this->plain($date);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/2020-01-01', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('2020-01-01', $output);

        $req = new Request('http://test.local/users/2020-06-01', 'get');
        try {
            $router->run($req);
            self::assertEquals('yes', 'no'); // should never reach
        } catch (RouteArgumentException $exception) {
            self::assertEquals('Date too far', $exception->getErrorMessage());
        }
    }

    public function testInvalidRestrictedArguments()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
                parent::restrictArgument('id', ["assf"]); // invalid rule
            }

            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };

        try {
            $controller->initializeRoutes();
            self::assertEquals('yes', 'no'); // should never reach
        } catch (\InvalidArgumentException $exception) {
            self::assertEquals("Specified rules for argument restrictions should be instance of Rule class", $exception->getMessage());
        }
    }

    public function testUnhandledErrorRestrictedArguments()
    {
        $this->expectException(RouteArgumentException::class);
        $this->expectExceptionMessage("The route argument {id} with value {jdsfjdsf} did not comply with defined rule and returned the following message : Invalid route argument");
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
            }

            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/jdsfjdsf', 'get');
        $router->run($req);
    }

    public function testHandledErrorRestrictedArguments()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
            }

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                return $this->plain("An error occurred for field " . $exception->getArgumentName() . " with value " . $exception->getValue() . " producing error " . $exception->getErrorMessage());
            }

            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/jdsfjdsf', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('An error occurred for field id with value jdsfjdsf producing error Invalid route argument', $output);
    }

    public function testHandledErrorRestrictedArgumentsWithId()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
                parent::restrictArgument('id', ['BAD' => Rule::integer("Invalid route argument")]);
            }

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                if ($exception->getRuleId() == "BAD") {
                    return $this->plain("An error occurred for field " . $exception->getArgumentName() . " with value " . $exception->getValue() . " producing error " . $exception->getErrorMessage());
                }
                return $this->plain("no");
            }

            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/jdsfjdsf', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('An error occurred for field id with value jdsfjdsf producing error Invalid route argument', $output);
    }
}
