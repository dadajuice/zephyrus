<?php namespace Zephyrus\Tests\Network;

use Phug\Util\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Request;
use Zephyrus\Network\Router;
use Zephyrus\Network\RouteRepository;
use Zephyrus\Network\Router\Get;

class RouteAttributeTest extends TestCase
{
    public function testGetRouting()
    {
        $controller = new class() extends Controller {

            #[Get("/")]
            public function index()
            {
                return $this->plain('index');
            }

            #[Get("/test")]
            public function test()
            {
                return $this->plain('test');
            }

            #[Router\Post("/login")]
            public function login()
            {
                return $this->plain('this is sparta');
            }

            #[Router\Put("/test")]
            public function update()
            {
                return $this->plain('this is update');
            }

            #[Router\Patch("/test")]
            public function partialUpdate()
            {
                return $this->plain('this is partial update');
            }

            #[Router\Delete("/test")]
            public function remove()
            {
                return $this->plain('this is delete');
            }
        };

        $repository = new RouteRepository();
        $controller->initializeRoutesFromAttributes($repository);

        $req = new Request('http://test.local/test', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());

        $req = new Request('http://test.local/login', 'post');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is sparta', $response->getContent());

        $req = new Request('http://test.local/test', 'put');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is update', $response->getContent());

        $req = new Request('http://test.local/test', 'patch');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is partial update', $response->getContent());

        $req = new Request('http://test.local/test', 'delete');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is delete', $response->getContent());
    }

    public function testRootGetRouting()
    {
        $controller = new AttributeExampleController();

        $repository = new RouteRepository();
        $controller->initializeRoutesFromAttributes($repository);

        $req = new Request('http://test.local/toto/test', 'get');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('test', $response->getContent());

        $req = new Request('http://test.local/toto/login', 'post');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is sparta', $response->getContent());

        $req = new Request('http://test.local/toto/test', 'put');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is update', $response->getContent());

        $req = new Request('http://test.local/toto/test', 'patch');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is partial update', $response->getContent());

        $req = new Request('http://test.local/toto/test', 'delete');
        $response = (new Router($repository))->resolve($req);
        self::assertEquals('this is delete', $response->getContent());
    }
}
