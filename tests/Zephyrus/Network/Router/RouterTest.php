<?php namespace Zephyrus\Network\Router;

use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpMethod;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Network\ServerEnvironnement;
use Zephyrus\Tests\RequestUtility;

class RouterTest extends TestCase
{
    public function testSimpleGetHome()
    {
        $req = RequestUtility::get("/");
        $repository = new RouteRepository();
        $repository->get('/', function() {
            return Response::builder()->plain("success");
        });
        $response = (new Router($repository))->resolve($req);
        $this->assertEquals("success", $response->getContent());
    }

    public function testSimpleGetResponse()
    {
        $req = RequestUtility::get("/");
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
        $req = RequestUtility::get("/bob");
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
        $req = RequestUtility::get("/bob/3");
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
        $req = RequestUtility::get("/bob/3");
        $ref = $this;
        $repository->get('/bob/{id}', function($id) use ($ref, $req) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $req->getRouteDefinition()->getArgument('id'));
        });
        (new Router($repository))->resolve($req);
    }

    public function testGetRequestWithTrailingSlash()
    {
        $repository = new RouteRepository();
        $req = RequestUtility::get("/bob/3/");
        $ref = $this;
        $repository->get('/bob/{id}', function($id) use ($ref, $req) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $req->getArgument('id'));
        });
        (new Router($repository))->resolve($req);
    }

    public function testMultipleGetRequest()
    {
        $repository = new RouteRepository();
        $req = RequestUtility::get("/bob/3/8");
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
        $this->expectException(Exception::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("success");

        $req = RequestUtility::get("/bob", [
            "HTTP_ACCEPT" => ContentType::JSON
        ]);
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
            throw new Exception('success', 500);
        }, [ContentType::JSON]);
        $router->resolve($req);
    }

    public function testRouteNotAcceptedException()
    {
        $this->expectException(RouteNotAcceptedException::class);

        $req = RequestUtility::get("/bob", [
            "HTTP_ACCEPT" => ContentType::HTML
        ]);
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
            return json_encode(['test' => 4]);
        }, [ContentType::JSON]);
        $router->resolve($req);
    }

    public function testRouteNotAcceptedCatch()
    {
        try {
            $req = RequestUtility::get("/bob", [
                "HTTP_ACCEPT" => ContentType::HTML
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
        $this->expectException(Exception::class);
        $this->expectExceptionCode(500);
        $this->expectExceptionMessage("success");

        $req = RequestUtility::get("/bob", [
            "HTTP_ACCEPT" => ContentType::HTML
        ]);
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
            throw new Exception('success', 500);
        }, [ContentType::JSON, ContentType::HTML]);
        $router->resolve($req);
    }

    public function testInvalidRouteMethod()
    {
        $this->expectException(RouteMethodUnsupportedException::class);

        $req = new Request(new ServerEnvironnement([
            'REQUEST_URI' => "/bob/3",
            'REQUEST_METHOD' => "INV",
            'CONTENT_TYPE' => ContentType::FORM
        ]));
        $repository = new RouteRepository();
        $router = new Router($repository);
        $router->resolve($req);
    }

    public function testInvalidRouteMethodCatch()
    {
        try {
            $req = new Request(new ServerEnvironnement([
                'REQUEST_URI' => "/bob/3",
                'REQUEST_METHOD' => "INV",
                'CONTENT_TYPE' => ContentType::FORM
            ]));
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
        $req = RequestUtility::get("/bob/3");
        $router = new Router($repository);
        $router->resolve($req);
    }

    public function testInvalidRouteCatch()
    {
        try {
            $req = RequestUtility::get("/bob/3");
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
        $this->expectException(InvalidArgumentException::class);

        $req = RequestUtility::get("/bob/3/5");
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob/{id}/{id}', function() {
            throw new Exception('success', 500);
        });
        $router->resolve($req);
    }

    public function testRouteNotFoundException()
    {
        $this->expectException(RouteNotFoundException::class);

        $req = RequestUtility::get("/sdfgg");
        $repository = new RouteRepository();
        $router = new Router($repository);
        $repository->get('/bob', function() {
        });
        $router->resolve($req);
    }

    public function testRouteConflictOrder()
    {
        $req = RequestUtility::get("/test/test");
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
