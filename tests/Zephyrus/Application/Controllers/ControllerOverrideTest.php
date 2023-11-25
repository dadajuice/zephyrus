<?php namespace Zephyrus\Tests\Application\Controllers;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\RouteArgumentException;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\Router\Get;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Tests\RequestUtility;

class ControllerOverrideTest extends TestCase
{
    public function testOverrideArguments()
    {
        $controller = new class() extends Controller {

            public function __construct()
            {
                $this->overrideArgument('user', function ($value) {
                    // Simulate database call ...
                    return (object) [
                        'id' => $value,
                        'username' => 'msandwich'
                    ];
                });
            }

            #[Get('/users/{user}')]
            public function read(stdClass $user): Response
            {
                $test = $this->request->getArgument("non-exist", "yes");
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id . $test);
            }
        };
        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $req = RequestUtility::get("/users/4");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('msandwich44yes', $response->getContent());
    }

    public function testOverrideArgumentsWithThrow()
    {
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

            public function handleRouteArgumentException(RouteArgumentException $exception): Response
            {
                $options = $exception->getOptions();
                assert(key_exists('something', $options));
                return $this->plain("user not found" . $exception->getOption('something'));
            }

            #[Get('/users/{user}')]
            public function read(stdClass $user): Response
            {
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $req = RequestUtility::get("/users/5");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('user not foundyes', $response->getContent());
    }

    public function testOverrideArgumentsWithResponse()
    {
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

            #[Get('/users/{user}')]
            public function read(stdClass $user): Response
            {
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $req = RequestUtility::get("/users/5");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('it worked', $response->getContent());
    }

    public function testOverrideNullArgument()
    {
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

            #[Get('/users/{user}')]
            public function read(?stdClass $user): Response
            {
                if (is_null($user)) {
                    return $this->plain("entity not found");
                }
                $id = $this->request->getArgument('user')->id; // request should be overridden too
                return $this->plain($user->username . $id . $user->id);
            }
        };
        $repository = new RouteRepository();
        $controller::initializeRoutes($repository);

        $req = RequestUtility::get("/users/9");
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('entity not found', $response->getContent());
    }

    public function testInvalidOverrideNotEnough()
    {
        try {
            new class() extends Controller {

                public function __construct()
                {
                    $this->overrideArgument('user', function () {
                        return "bob";
                    });
                }

                #[Get('/users/{user}')]
                public function read(stdClass $user): Response
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
            new class() extends Controller {

                public function __construct()
                {
                    $this->overrideArgument('user', function ($value, $bob, $lewis) {
                        return "bob";
                    });
                }

                #[Get('/users/{user}')]
                public function read(stdClass $user): Response
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
