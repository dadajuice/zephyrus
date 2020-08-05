<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\Request;

class ControllerOverrideTest extends TestCase
{
    public function testOverrideArguments()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideArgument('user', function ($value) {
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            public function read(\stdClass $user)
            {
                $id = $this->request->getParameter('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/4', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('msandwich44', $output);
    }

    public function testOverrideArgumentsWithThrow()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideArgument('user', function ($value) {
                    if ($value != 4) { // simulate not found
                        throw new RouteArgumentException("user", $value, 0, "user not found");
                    }
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                return $this->plain("User not found");
            }

            public function read(\stdClass $user)
            {
                $id = $this->request->getParameter('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/5', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('User not found', $output);
    }

    public function testOverrideNullArgument()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideArgument('user', function ($value) {
                    if ($value == 4) {
                        return (object) [
                            'id' => $value,
                            'username' => 'msandwich'
                        ];
                    }
                    return null;
                });
            }

            public function read(?\stdClass $user)
            {
                if (is_null($user)) {
                    return $this->plain("entity not found");
                }
                $id = $this->request->getParameter('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/9', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('entity not found', $output);
    }

    public function testInvalidOverrideNotEnough()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideArgument('user', function () {
                    return "bob";
                });
            }

            public function read(\stdClass $user)
            {
                $id = $this->request->getParameter('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };

        try {
            $controller->initializeRoutes();
            self::assertEquals('yes', 'no'); // should never reach
        } catch (\InvalidArgumentException $exception) {
            self::assertEquals("Override callback should have only one argument which will contain the value of the associated argument name", $exception->getMessage());
        }
    }

    public function testInvalidOverrideTooMuch()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideArgument('user', function ($value, $bob, $lewis) {
                    return "bob";
                });
            }

            public function read(\stdClass $user)
            {
                $id = $this->request->getParameter('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };

        try {
            $controller->initializeRoutes();
            self::assertEquals('yes', 'no'); // should never reach
        } catch (\InvalidArgumentException $exception) {
            self::assertEquals("Override callback should have only one argument which will contain the value of the associated argument name", $exception->getMessage());
        }
    }
}
