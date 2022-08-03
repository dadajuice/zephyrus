<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

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
                $test = $this->request->getArgument("non-exist", "yes");
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id . $test);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/4', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('msandwich44yes', $output);
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

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                $options = $exception->getOptions();
                assert(key_exists('something', $options));
                return $this->plain("user not found" . $exception->getOption('something'));
            }

            public function read(\stdClass $user)
            {
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/5', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('user not foundyes', $output);
    }

    public function testOverrideArgumentsWithResponse()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideArgument('user', function ($value) {
                    if ($value != 4) { // simulate not found
                        return $this->plain("it worked");
                    }
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            public function read(\stdClass $user)
            {
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/5', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();

        self::assertEquals('it worked', $output);
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
                $id = $this->request->getArgument('user')->id; // request should be overridden too
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
                $id = $this->request->getArgument('user')->id; // request should be overridden too
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
                $id = $this->request->getArgument('user')->id; // request should be overridden too
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
