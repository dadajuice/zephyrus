<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\RouteMethodUnsupportedException;
use Zephyrus\Exceptions\RouteNotAcceptedException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

class RouterTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionCode 501
     */
    public function testSimpleGetHome()
    {
        $req = new Request('http://test.local/', 'GET');
        $router = new Router();
        $ref = $this;
        $router->get('/', function() use ($ref) {
            throw new \Exception('success', 501);
        });
        $router->run($req);
    }

    public function testSimpleGetResponse()
    {
        $req = new Request('http://test.local/', 'GET');
        $router = new Router();
        $router->get('/', function() {
            $response = new Response();
            $response->setContent('test');
            return $response;
        });
        ob_start();
        $router->run($req);
        $output = ob_get_clean();
        $this->assertEquals('test', $output);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 500
     */
    public function testSimpleGet()
    {
        $req = new Request('http://test.local/bob', 'GET');
        $router = new Router();
        $router->get('/bob', function() {
            throw new \Exception('success', 500);
        });
        $router->run($req);
    }

    public function testParameterGet()
    {
        $req = new Request('http://test.local/bob/3', 'GET');
        $router = new Router();
        $ref = $this;
        $router->get('/bob/{id}', function($id) use ($ref, $req) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $req->getParameter('id'));
        });
        $router->run($req);
    }

    public function testGetRequest()
    {
        $req = new Request('http://test.local/bob/3', 'GET');
        $router = new Router();
        $ref = $this;
        $router->get('/bob/{id}', function($id) use ($ref, $router) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('3', $router->getRequest()->getParameter('id'));
        });
        $router->run($req);
    }

    public function testMultipleGetRequest()
    {
        $req = new Request('http://test.local/bob/3/8', 'GET');
        $router = new Router();
        $ref = $this;
        $router->get('/bob/{id}/{id2}', function($id, $id2) use ($ref, $router) {
            $ref->assertEquals('3', $id);
            $ref->assertEquals('8', $id2);
            $ref->assertEquals('3', $router->getRequest()->getParameter('id'));
            $ref->assertEquals('8', $router->getRequest()->getParameter('id2'));
        });
        $router->run($req);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 500
     */
    public function testAccept()
    {
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::JSON;
        $req = new Request('http://test.local/bob', 'GET', [
            'server' => $server
        ]);
        $router = new Router();
        $router->get('/bob', function() {
            throw new \Exception('success', 500);
        }, ContentType::JSON);
        $router->run($req);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\RouteNotAcceptedException
     */
    public function testRouteNotAcceptedException()
    {
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::HTML;
        $req = new Request('http://test.local/bob', 'GET', [
            'server' => $server
        ]);
        $router = new Router();
        $router->get('/bob', function() {

        }, [ContentType::JSON]);
        $router->run($req);
    }

    public function testRouteNotAcceptedCatch()
    {
        try {
            $server = [];
            $server['HTTP_ACCEPT'] = ContentType::HTML;
            $req = new Request('http://test.local/bob', 'GET', [
                'server' => $server
            ]);
            $router = new Router();
            $router->get('/bob', function() {

            }, [ContentType::JSON]);
            $router->run($req);
        } catch (RouteNotAcceptedException $e) {
            self::assertEquals(ContentType::HTML, $e->getAccept());
        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 500
     */
    public function testMultipleRouteNotAcceptedException()
    {
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::HTML;
        $req = new Request('http://test.local/bob', 'GET', [
            'server' => $server
        ]);
        $router = new Router();
        $router->get('/bob', function() {
            throw new \Exception('success', 500);
        }, [ContentType::JSON, ContentType::HTML]);
        $router->run($req);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\RouteMethodUnsupportedException
     */
    public function testInvalidRouteMethod()
    {
        $req = new Request('http://test.local/bob/3', 'INV');
        $router = new Router();
        $router->run($req);
    }

    public function testInvalidRouteMethodCatch()
    {
        try {
            $req = new Request('http://test.local/bob/3', 'INV');
            $router = new Router();
            $router->run($req);
        } catch (RouteMethodUnsupportedException $e) {
            self::assertEquals('INV', $e->getMethod());
        }
    }

    /**
     * @expectedException \Zephyrus\Exceptions\RouteNotFoundException
     */
    public function testInvalidRoute()
    {
        $req = new Request('http://test.local/bob/3', 'GET');
        $router = new Router();
        $router->run($req);
    }

    public function testInvalidRouteCatch()
    {
        try {
            $req = new Request('http://test.local/bob/3', 'GET');
            $router = new Router();
            $router->run($req);
        } catch (RouteNotFoundException $e) {
            self::assertEquals('GET', $e->getMethod());
            self::assertEquals('/bob/3', $e->getUri());
        }
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidRouteDefinition()
    {
        $req = new Request('http://test.local/bob/3/5', 'GET');
        $router = new Router();
        $router->get('/bob/{id}/{id}', function() {
            throw new \Exception('success', 500);
        });
        $router->run($req);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\RouteNotFoundException
     */
    public function testRouteNotFoundException()
    {
        $req = new Request('http://test.local/sdfgg', 'GET');
        $router = new Router();
        $router->get('/bob', function() {
        });
        $router->run($req);
    }
}