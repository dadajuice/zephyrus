<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

class ControllerRenderTest extends TestCase
{
    public function testRender()
    {
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $req = new Request('http://test.local', 'GET', [
            'server' => $server
        ]);
        RequestFactory::set($req);

        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                return parent::render('test', ['item' => ['name' => 'Bob Lewis', 'price' => 12.30]]);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<p>Bob Lewis</p>', $output);
    }

    public function testRenderPhp()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                return parent::renderPhp('test2', ['a' => 'allo']);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<h1>allo</h1>', $output);
    }

    public function testRenderUnavailablePhp()
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage("The specified view file [dfgdfgdfg] is not available (not readable or does not exists)");
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                return parent::renderPhp('dfgdfgdfg', ['a' => 'allo']);
            }
        };
        $controller->index()->send();
    }

    public function testRenderUnavailablePug()
    {
        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage("The specified view file [dfgdfgdfg] is not available (not readable or does not exists)");
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                return parent::render('dfgdfgdfg', ['a' => 'allo']);
            }
        };
        $controller->index()->send();
    }

    public function testRenderPhpWithFlashAndFeedback()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::error(["email" => ["test"]]);
                return parent::renderPhp('test3');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<h1>invalid</h1><h1>test</h1>', $output);
    }

    public function testRenderPugWithFlashAndFeedback()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::error(["email" => ["test"]]);
                return parent::render('test4');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<h1>invalid</h1><h1>test</h1>', $output);
    }

    public function testJson()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::json(['test' => ['a' => 2, 'b' => 'bob']]);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('{"test":{"a":2,"b":"bob"}}', $output);
    }

    public function testHtml()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::html("<html>test</html>");
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<html>test</html>', $output);
    }

    public function testPlain()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::plain("test plain");
            }
        };
        ob_start();
        $controller->index()->send();
        $headers = xdebug_get_headers();
        $output = ob_get_clean();
        self::assertEquals('test plain', $output);
        self::assertTrue(in_array('Content-Type: text/plain;charset=UTF-8', $headers));
    }
}