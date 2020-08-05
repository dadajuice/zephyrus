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
                return $this->plain($user->username . $user->id);
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/4', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('msandwich4', $output);
    }
}
