<?php namespace Zephyrus\Tests\application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Router;

class ControllerSuccessTest extends TestCase
{
    public function testCreated()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::created("/allo");
            }
        };
        $controller->index()->send();
        self::assertEquals(201, http_response_code());
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Location:/allo', $headers));
    }

    public function testCreatedWithBody()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::created("/allo", json_encode(['user' => 'bob lewis']), ContentType::JSON);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('{"user":"bob lewis"}', $output);

    }

    public function testRedirect()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::redirect('/test');
            }
        };
        $controller->index()->send();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Location:/test', $headers));
    }
}