<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
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
        $ref = $this;
        $router->get('/bob', function() use ($ref) {
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
}