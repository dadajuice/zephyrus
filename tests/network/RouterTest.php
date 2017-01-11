<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
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

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 500
     */
    public function testAccept()
    {
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::JSON;
        $req = new Request('http://test.local/bob', 'GET', [], [], [], $server);
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
        $req = new Request('http://test.local/bob', 'GET', [], [], [], $server);
        $router = new Router();
        $router->get('/bob', function() {

        }, [ContentType::JSON]);
        $router->run($req);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionCode 500
     */
    public function testMultipleRouteNotAcceptedException()
    {
        $server = [];
        $server['HTTP_ACCEPT'] = ContentType::HTML;
        $req = new Request('http://test.local/bob', 'GET', [], [], [], $server);
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

    /**
     * @expectedException \Zephyrus\Exceptions\RouteNotFoundException
     */
    public function testInvalidRoute()
    {
        $req = new Request('http://test.local/bob/3', 'GET');
        $router = new Router();
        $router->run($req);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\RouteDefinitionException
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