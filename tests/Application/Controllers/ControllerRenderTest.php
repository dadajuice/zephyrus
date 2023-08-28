<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Feedback;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Views\PugEngine;
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

        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
            }

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::register(["email" => ["test"]]);
                return parent::renderPhp('test3', [
                    'flash' => Flash::readAll(),
                    'feedback' => Feedback::readAll()
                ]);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertEquals('<h1>invalid</h1><h1>test</h1>', $output);
    }

    public function testRenderPugWithFlashAndFeedback()
    {
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
            }

            public function index(): Response
            {
                Flash::error("invalid");
                Feedback::register(["email" => ["test"]]);
                $engine = new PugEngine(['cache_enabled' => false]);
                $engine->share('pug_flash', function () {
                    return Flash::readAll();
                });
                $this->setRenderEngine($engine);
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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