<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Rule;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Tests\RequestUtility;

class ControllerRestrictedParameterTest extends TestCase
{
    public function testSuccessfulRestrictedArguments()
    {
        $controller = new class() extends Controller {

            public function __construct()
            {
                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
            }

            #[Get('/users/{id}')]
            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };

        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $req = RequestUtility::get('/users/4');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('4', $response->getContent());
    }

    public function testSuccessfulRestrictedArgumentsMerge()
    {
        $controller = new class() extends Controller {

            public function __construct()
            {
                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
                parent::restrictArgument('id', [Rule::range(1, 10, "Invalid route range")]);
            }

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                return $this->plain("failed!" . $exception->getRuleId());
            }

            #[Get('/users/{id}')]
            public function read(int $userId)
            {
                return $this->plain($userId);
            }
        };
        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $req = RequestUtility::get('/users/40');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('failed!1', $response->getContent());
    }

//    public function testSuccessfulMultipleRestrictedArguments()
//    {
//        $repository = new RouteRepository();
//        $controller = new class() extends Controller {
//
//            public function __construct()
//            {
//                parent::restrictArgument('date', [
//                    Rule::date("Invalid route argument"),
//                    Rule::dateBefore('2020-05-01', "Date too far")
//                ]);
//            }
//
//            public function initializeRoutes(): void
//            {
//                parent::get('/users/{date}', 'read');
//            }
//
//            public function read($date)
//            {
//                return $this->plain($date);
//            }
//        };
//        $controller->setRouteRepository($repository);
//        $controller->initializeRoutes();
//        $req = new Request('http://test.local/users/2020-01-01', 'get');
//        $response = (new Router($repository))->resolve($req);
//        self::assertEquals('2020-01-01', $response->getContent());
//
//        $req = new Request('http://test.local/users/2020-06-01', 'get');
//        try {
//            (new Router($repository))->resolve($req);
//            self::assertEquals('yes', 'no'); // should never reach
//        } catch (RouteArgumentException $exception) {
//            self::assertEquals('Date too far', $exception->getErrorMessage());
//        }
//    }
//
//    public function testInvalidRestrictedArguments()
//    {
//        try {
//            $controller = new class() extends Controller {
//
//                public function __construct()
//                {
//                    parent::restrictArgument('id', ["assf"]); // invalid rule
//                }
//
//                public function initializeRoutes(): void
//                {
//                    parent::get('/users/{id}', 'read');
//
//                }
//
//                public function read(int $userId)
//                {
//                    return $this->plain($userId);
//                }
//            };
//            self::assertEquals('yes', 'no'); // should never reach
//        } catch (\InvalidArgumentException $exception) {
//            self::assertEquals("Specified rules for argument restrictions should be instance of Rule class", $exception->getMessage());
//        }
//    }
//
//    public function testUnhandledErrorRestrictedArguments()
//    {
//        $repository = new RouteRepository();
//        $this->expectException(RouteArgumentException::class);
//        $this->expectExceptionMessage("The route argument {id} with value {jdsfjdsf} did not comply with defined rule and returned the following message : Invalid route argument");
//        $controller = new class() extends Controller {
//
//            public function __construct()
//            {
//                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
//            }
//
//            public function initializeRoutes(): void
//            {
//                parent::get('/users/{id}', 'read');
//            }
//
//            public function read(int $userId)
//            {
//                return $this->plain($userId);
//            }
//        };
//        $controller->setRouteRepository($repository);
//        $controller->initializeRoutes();
//        $req = new Request('http://test.local/users/jdsfjdsf', 'get');
//        (new Router($repository))->resolve($req);
//    }
//
//    public function testHandledErrorRestrictedArguments()
//    {
//        $repository = new RouteRepository();
//        $controller = new class() extends Controller {
//
//            public function __construct()
//            {
//                parent::restrictArgument('id', [Rule::integer("Invalid route argument")]);
//            }
//
//            public function initializeRoutes(): void
//            {
//                parent::get('/users/{id}', 'read');
//            }
//
//            public function handleRouteArgumentException(RouteArgumentException $exception): Response
//            {
//                return $this->plain("An error occurred for field " . $exception->getArgumentName() . " with value " . $exception->getValue() . " producing error " . $exception->getErrorMessage());
//            }
//
//            public function read(int $userId)
//            {
//                return $this->plain($userId);
//            }
//        };
//        $controller->setRouteRepository($repository);
//        $controller->initializeRoutes();
//        $req = new Request('http://test.local/users/jdsfjdsf', 'get');
//        $response = (new Router($repository))->resolve($req);
//        self::assertEquals('An error occurred for field id with value jdsfjdsf producing error Invalid route argument', $response->getContent());
//    }
//
//    public function testHandledErrorRestrictedArgumentsWithId()
//    {
//        $repository = new RouteRepository();
//        $controller = new class() extends Controller {
//
//            public function __construct()
//            {
//                parent::restrictArgument('id', ['BAD' => Rule::integer("Invalid route argument")]);
//            }
//
//            public function initializeRoutes(): void
//            {
//                parent::get('/users/{id}', 'read');
//            }
//
//            public function handleRouteArgumentException(RouteArgumentException $exception): Response
//            {
//                if ($exception->getRuleId() == "BAD") {
//                    return $this->plain("An error occurred for field " . $exception->getArgumentName() . " with value " . $exception->getValue() . " producing error " . $exception->getErrorMessage());
//                }
//                return $this->plain("no");
//            }
//
//            public function read(int $userId)
//            {
//                return $this->plain($userId);
//            }
//        };
//        $controller->setRouteRepository($repository);
//        $controller->initializeRoutes();
//        $req = new Request('http://test.local/users/jdsfjdsf', 'get');
//        $response = (new Router($repository))->resolve($req);
//        self::assertEquals('An error occurred for field id with value jdsfjdsf producing error Invalid route argument', $response->getContent());
//    }
}
