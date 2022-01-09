<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Bootstrap;
use Zephyrus\Network\Router;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Request;

class BootstrapTest extends TestCase
{
    public function testGetFunctionPath()
    {
        $path = Bootstrap::getHelperFunctionsPath();
        $info = pathinfo($path, PATHINFO_BASENAME);
        self::assertEquals("functions.php", $info);
    }

    public function testStart()
    {
        Bootstrap::start();
        self::assertEquals('America/New_York', date_default_timezone_get());
        self::assertEquals('1', ini_get('display_errors'));
        self::assertEquals('1', ini_get('display_startup_errors'));
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

        // Mimics workflow
        $req = new Request('http://test.local/batman', 'get', [
            'server' => $server
        ]);
        $router = new Router();
        Bootstrap::initializeRoutableControllers($router);
        ob_start();
        $router->run($req);
        self::assertEquals('batman rocks!', ob_get_clean());
    }
}