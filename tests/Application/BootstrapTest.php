<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Bootstrap;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Router;
use Zephyrus\Network\RouteRepository;

class BootstrapTest extends TestCase
{
    public function testGetFunctionPath()
    {
        $path = Bootstrap::getHelperFunctionsPath();
        $info = pathinfo($path, PATHINFO_BASENAME);
        self::assertEquals("functions.php", $info);
    }

    public function testController()
    {
        $server['REQUEST_METHOD'] = 'GET';
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['REQUEST_URI'] = 'http://test.local/batman';
        $server['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $server['HTTP_HOST'] = 'test.local';
        $server['SERVER_PORT'] = '80';
        $server['CONTENT_TYPE'] = ContentType::PLAIN;

        $repository = new RouteRepository();
        Bootstrap::initializeControllerRoutes($repository);

        // Mimics workflow
        $req = new Request('http://test.local/batman', 'get', [
            'server' => $server
        ]);
        $router = new Router($repository);
        $response = $router->resolve($req);
        self::assertEquals('batman rocks!', $response->getContent());

        $req = new Request('http://test.local/robin', 'get', [
            'server' => $server
        ]);
        $response = $router->resolve($req);
        self::assertEquals('robin test', $response->getContent());
    }
}