<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Router;
use Zephyrus\Network\Request;

class ControllerOverrideTest extends TestCase
{
    public function testOverrideParameters()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideParameter('user', function ($value) {
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

    public function testOverrideNullParameters()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{user}', 'read');

                parent::overrideParameter('user', function ($value) {
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
}
