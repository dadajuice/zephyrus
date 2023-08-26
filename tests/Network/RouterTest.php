<?php namespace Zephyrus\Tests\Network;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ResponseFactory;
use Zephyrus\Network\Router;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\RouteRepository;

class RouterTest extends TestCase
{
    public function testSimpleGetHome()
    {
        $req = new Request('http://test.local/', 'GET');
        $repository = new RouteRepository();
        $repository->get('/', function() {
            return ResponseFactory::getInstance()->plain("success");
        });
        $response = (new Router($repository))->resolve($req);
        self::assertEquals("success", $response->getContent());
    }

    public function testSimpleGetResponse()
    {
        $req = new Request('http://test.local/', 'GET');
        $repository = new RouteRepository();
        $repository->get('/', function() {
            $response = new Response();
            $response->setContent('test');
            return $response;
        });
        $response = (new Router($repository))->resolve($req);
        $this->assertEquals('test', $response->getContent());
    }

    public function testSimpleGet()
    {
        $repository = new RouteRepository();
        $req = new Request('http://test.local/bob', 'GET');
        $repository->get('/bob', function() {
            $response = new Response();
            $response->setContent('test');
            return $response;
        });
        $response = (new Router($repository))->resolve($req);
        $this->assertEquals('test', $response->getContent());
    }

    public function testParameterGet()
    {
        $repository = new RouteRepository();
        $req = new Request('http://test.local/bob/3', 'GET');

        $ref = $this;
        $repository->get('/bob/{id}', function($id) use ($ref, $req) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $req->getArgument('id'));
        });
        (new Router($repository))->resolve($req);
    }

    public function testGetRequest()
    {
        $repository = new RouteRepository();
        $req = new Request('http://test.local/bob/3', 'GET');
        $router = new Router($repository);
        $ref = $this;
        $repository->get('/bob/{id}', function($id) use ($ref, $router) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $router->getRequest()->getArgument('id'));
        });
        $router->resolve($req);
    }

    public function testGetRequestWithLeadingSlash()
    {
        $repository = new RouteRepository();
        $req = new Request('http://test.local/bob/3/', 'GET');
        $router = new Router($repository);
        $ref = $this;
        $repository->get('/bob/{id}', function($id) use ($ref, $router) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $router->getRequest()->getArgument('id'));
        });
        $router->resolve($req);
    }

    public function testMultipleGetRequest()
    {
        $repository = new RouteRepository();
        $req = new Request('http://test.local/bob/3/8', 'GET');
        $router = new Router($repository);
        $ref = $this;
        $repository->get('/bob/{id}/{id2}', function($id, $id2) use ($ref, $router) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('8', $id2);
            $ref->assertEquals('3', $router->getRequest()->getArgument('id'));
            $ref->assertEquals('8', $router->getRequest()->getArgument('id2'));
        });
        $router->resolve($req);
    }

    public function testAccept()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(500);
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::JSON;
        $req = new Request('http://test.local/bob', 'GET', [
            'server' => $server
        ]);
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
            throw new \Exception('success', 500);
        }, ContentType::JSON);
        $router->resolve($req);
    }

    public function testRouteNotAcceptedException()
    {
        $this->expectException(RouteNotAcceptedException::class);
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::HTML;
        $req = new Request('http://test.local/bob', 'GET', [
            'server' => $server
        ]);
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {

        }, [ContentType::JSON]);
        $router->resolve($req);
    }

    public function testRouteNotAcceptedCatch()
    {
        try {
            $server = [];
            $server['HTTP_ACCEPT'] = ContentType::HTML;
            $req = new Request('http://test.local/bob', 'GET', [
                'server' => $server
            ]);
            $repository = new RouteRepository();
            $router = new Router($repository);
            $repository->get('/bob', function() {

            }, [ContentType::JSON]);
            $router->resolve($req);
        } catch (RouteNotAcceptedException $e) {
            self::assertEquals(ContentType::HTML, $e->getAccept());
        }
    }

    public function testMultipleRouteNotAcceptedException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(500);
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::HTML;
        $req = new Request('http://test.local/bob', 'GET', [
            'server' => $server
        ]);
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
            throw new \Exception('success', 500);
        }, [ContentType::JSON, ContentType::HTML]);
        $router->resolve($req);
    }

    public function testInvalidRouteMethod()
    {
        $this->expectException(RouteMethodUnsupportedException::class);
        $req = new Request('http://test.local/bob/3', 'INV');
        $repository = new RouteRepository();
        $router = new Router($repository);
        $router->resolve($req);
    }

    public function testInvalidRouteMethodCatch()
    {
        try {
            $req = new Request('http://test.local/bob/3', 'INV');
            $repository = new RouteRepository();
            $router = new Router($repository);
            $router->resolve($req);
        } catch (RouteMethodUnsupportedException $e) {
            self::assertEquals('INV', $e->getMethod());
        }
    }

    public function testInvalidRoute()
    {
        $this->expectException(RouteNotFoundException::class);
        $repository = new RouteRepository();
        $req = new Request('http://test.local/bob/3', 'GET');
        $router = new Router($repository);
        $router->resolve($req);
    }

    public function testInvalidRouteCatch()
    {
        try {
            $req = new Request('http://test.local/bob/3', 'GET');
            $repository = new RouteRepository();
            $router = new Router($repository);
            $router->resolve($req);
        } catch (RouteNotFoundException $e) {
            self::assertEquals('GET', $e->getMethod());
            self::assertEquals('/bob/3', $e->getUri());
        }
    }

    public function testInvalidRouteDefinition()
    {
        $this->expectException(\InvalidArgumentException::class);
        $req = new Request('http://test.local/bob/3/5', 'GET');
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob/{id}/{id}', function() {
            throw new \Exception('success', 500);
        });
        $router->resolve($req);
    }

    public function testRouteNotFoundException()
    {
        $this->expectException(RouteNotFoundException::class);
        $req = new Request('http://test.local/sdfgg', 'GET');
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
        });
        $router->resolve($req);
    }

    public function testRouteConflictOrder()
    {
        $req = new Request('http://test.local/test/test', 'GET');
        $repository = new RouteRepository();
        $router = new Router($repository);

        $repository->get('/test/{id}', function($id) {
            $response = new Response();
            $response->setContent('test1');
            return $response;

        });
        $repository->get('/test/test', function() {
            $response = new Response();
            $response->setContent('test2');
            return $response;

        });

        $response = $router->resolve($req);
        self::assertEquals("test2", $response->getContent());
    }
}