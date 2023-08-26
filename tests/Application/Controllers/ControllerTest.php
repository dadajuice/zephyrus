<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\RouteRepository;

class ControllerTest extends TestCase
{
    public function testGetRouting()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::get('/', 'index');
            }

            public function index()
            {
                return $this->html('test');
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());
    }

    public function testBeforeMiddleware()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('{"test":"success"}', $response->getContent());
    }

    public function testAfterMiddleware()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('{"test":"success"}', $response->getContent());
    }

    public function testGetRoutingWithParameter()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::get('/bob/{id}', 'index');
            }

            public function index($id)
            {
                return $this->plain('test' . $id);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/bob/4', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test4', $response->getContent());
    }

    public function testPostRouting()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::post('/insert', 'insert');
            }

            public function insert()
            {
                return $this->plain('test');
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/insert', 'post');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());
    }

    public function testPutRouting()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::put('/update', 'update');
            }

            public function update()
            {
                return $this->plain('test');
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/update', 'put');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());
    }

    public function testPatchRouting()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::patch('/update', 'update');
            }

            public function update()
            {
                return $this->plain('test');
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/update', 'patch');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());
    }

    public function testDeleteRouting()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::delete('/delete', 'remove');
            }

            public function remove()
            {
                $t = $this->request->getParameter('t');
                return $this->plain('test' . $t);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/delete', 'delete', ['parameters' => ['t' => '4']]);
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test4', $response->getContent());
    }

    public function testBuildForm()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::delete('/delete', 'remove');
            }

            public function remove()
            {
                $form = parent::buildForm();
                $t = $form->getValue('t');
                return $this->plain('test' . $t);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/delete', 'delete', [
            'parameters' => ['t' => '4']
        ]);
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test4', $response->getContent());
    }

    public function testBuildFormWithArguments()
    {
        $repository = new RouteRepository();
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
            {
                parent::get('/users/{id}', 'read');
            }

            public function read($userId)
            {
                $form = parent::buildForm(true);
                $t = $form->getValue('t');
                $id = $form->getValue('id');
                return $this->plain('test' . $t . $id);
            }
        };
        $controller->setRouteRepository($repository);
        $controller->initializeRoutes();
        $req = new Request('http://test.local/users/99?t=4', 'get', [
            'parameters' => ['t' => '4']
        ]);
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test499', $response->getContent());
    }
}
