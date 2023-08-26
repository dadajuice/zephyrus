<?php namespace Zephyrus\Tests\Network;

use Phug\Util\TestCase;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;
use Zephyrus\Network\Router;
use Zephyrus\Network\RouteRepository;

class RouteRepositoryTest extends TestCase
{
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
        $repository->initializeFromControllers();

        // Mimics workflow
        $req = new Request('http://test.local/batman', 'get', [
            'server' => $server
        ]);
        $router = new Router($repository);
        $response = $router->resolve($req);
        self::assertEquals('batman rocks!', $response->getContent());
    }
}
