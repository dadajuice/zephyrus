<?php namespace Zephyrus\Tests\Application\Controllers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\RouteRepository;

class ControllerOverrideTest extends TestCase
{
    public function testOverrideArguments()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function __construct()
            {
                $this->overrideArgument('user', function ($value) {
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            public function initializeRoutes(): void
            {
                $this->get('/users/{user}', 'read');
            }

            public function read(stdClass $user)
            {
                $test = $this->request->getArgument("non-exist", "yes");
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id . $test);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/4', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('msandwich44yes', $response->getContent());
    }

    public function testOverrideArgumentsWithThrow()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function __construct()
            {
                $this->overrideArgument('user', function ($value) {
                    if ($value != 4) { // simulate not found
                        $e = new RouteArgumentException("user", $value, 0, "user not found");
                        $e->addOption('something', 'yes');
                        throw $e;
                    }
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            public function initializeRoutes(): void
            {
                $this->get('/users/{user}', 'read');
            }

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                $options = $exception->getOptions();
                assert(key_exists('something', $options));
                return $this->plain("user not found" . $exception->getOption('something'));
            }

            public function read(stdClass $user)
            {
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/5', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('user not foundyes', $response->getContent());
    }

    public function testOverrideArgumentsWithResponse()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function __construct()
            {
                $this->overrideArgument('user', function ($value) {
                    if ($value != 4) { // simulate not found
                        return $this->plain("it worked");
                    }
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            public function initializeRoutes(): void
            {
                $this->get('/users/{user}', 'read');
            }

            public function read(stdClass $user)
            {
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/5', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('it worked', $response->getContent());
    }

    public function testOverrideNullArgument()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function __construct()
            {
                $this->overrideArgument('user', function ($value) {
                    if ($value == 4) {
                        return (object) [
                            'id' => $value,
                            'username' => 'msandwich'
                        ];
                    }
                    return null;
                });
            }

            public function initializeRoutes(): void
            {
                $this->get('/users/{user}', 'read');
            }

            public function read(?stdClass $user)
            {
                if (is_null($user)) {
                    return $this->plain("entity not found");
                }
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/9', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('entity not found', $response->getContent());
    }

    public function testInvalidOverrideNotEnough()
    {
        try {
            $controller = new class() extends Controller {

                public function __construct()
                {
                    $this->overrideArgument('user', function () {
                        return "bob";
                    });
                }

                public function initializeRoutes(): void
                {
                    $this->get('/users/{user}', 'read');
                }

                public function read(stdClass $user)
                {
                    $id = $this->request->getArgument('user')->id; // request should be overridden too
                    return $this->plain($user->username . $id . $user->id);
                }
            };
            self::assertEquals('yes', 'no'); // should never reach
        } catch (InvalidArgumentException $exception) {
            self::assertEquals("Override callback should have only one argument which will contain the value of the associated argument name", $exception->getMessage());
        }
    }

    public function testInvalidOverrideTooMuch()
    {
        try {
            $controller = new class() extends Controller {

                public function __construct()
                {
                    $this->overrideArgument('user', function ($value, $bob, $lewis) {
                        return "bob";
                    });
                }

                public function initializeRoutes(): void
                {
                    $this->get('/users/{user}', 'read');
                }

                public function read(stdClass $user)
                {
                    $id = $this->request->getArgument('user')->id; // request should be overridden too
                    return $this->plain($user->username . $id . $user->id);
                }
            };
            self::assertEquals('yes', 'no'); // should never reach
        } catch (InvalidArgumentException $exception) {
            self::assertEquals("Override callback should have only one argument which will contain the value of the associated argument name", $exception->getMessage());
        }
    }
}
