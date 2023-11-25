<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Bootstrap;
use Zephyrus\Network\Router;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Tests\RequestUtility;

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
        $repository = new RouteRepository();
        Bootstrap::initializeControllerRoutes($repository);
        $router = new Router($repository);

        $request = RequestUtility::get("/batman");
        $response = $router->resolve($request);
        self::assertEquals('batman rocks!', $response->getContent());

        $request = RequestUtility::get("/robin");
        $response = $router->resolve($request);
        self::assertEquals('robin test', $response->getContent());
    }
}