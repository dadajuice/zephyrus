<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Router;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;

class ControllerTest extends TestCase
{
    public function testGetRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
            }

            public function index()
            {
                return $this->html('test');
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testBeforeMiddleware()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
            }

            public function before(): ?Response
            {
                return $this->json(['test' => 'success']);
            }

            public function index()
            {
                return $this->html('test html');
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('{"test":"success"}', $output);
    }

    public function testAfterMiddleware()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/', 'index');
            }

            public function after(?Response $response): ?Response
            {
                return $this->json(['test' => 'success']);
            }

            public function index()
            {
                return $this->html('test html');
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('{"test":"success"}', $output);
    }

    public function testGetRoutingWithParameter()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/bob/{id}', 'index');
            }

            public function index($id)
            {
                echo 'test' . $id;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/bob/4', 'get');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test4', $output);
    }

    public function testPostRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::post('/insert', 'insert');
            }

            public function insert()
            {
                echo 'test';
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/insert', 'post');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testPutRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::put('/update', 'update');
            }

            public function update()
            {
                echo 'test';
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/update', 'put');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testPatchRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::patch('/update', 'update');
            }

            public function update()
            {
                echo 'test';
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/update', 'patch');
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test', $output);
    }

    public function testDeleteRouting()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::delete('/delete', 'remove');
            }

            public function remove()
            {
                $t = $this->request->getParameter('t');
                echo 'test' . $t;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/delete', 'delete', ['parameters' => ['t' => '4']]);
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test4', $output);
    }

    public function testBuildForm()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::delete('/delete', 'remove');
            }

            public function remove()
            {
                $form = parent::buildForm();
                $t = $form->getValue('t');
                echo 'test' . $t;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/delete', 'delete', [
            'parameters' => ['t' => '4']
        ]);
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test4', $output);
    }

    public function testBuildFormWithArguments()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
                parent::get('/users/{id}', 'read');
            }

            public function read($userId)
            {
                $form = parent::buildForm(true);
                $t = $form->getValue('t');
                $id = $form->getValue('id');
                echo 'test' . $t . $id;
            }
        };
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/99?t=4', 'get', [
            'parameters' => ['t' => '4']
        ]);
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        self::assertEquals('test499', $output);
    }
}
