<?php namespace Zephyrus\Tests\Network;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Route;

class RouteTest extends TestCase
{
    public function testGetSimpleArgument()
    {
        $route = new Route("/dummy/{id}");
        $args = $route->getArguments("/dummy/4");
        self::assertEquals('/dummy/{id}', $route->getUri());
        self::assertEquals(['id' => "4"], $args);
    }

    public function testGetComplexArgument()
    {
        $route = new Route("/dummy/{id}/{fish}");
        $args = $route->getArguments("/dummy/4/goldfish");
        self::assertEquals(['id' => "4", 'fish' => "goldfish"], $args);
    }

    public function testRegex()
    {
        $route = new Route("/dummy/.+");
        self::assertEquals(true, $route->match("/dummy/3"));
        self::assertEquals(true, $route->match("/dummy/3/info"));
        self::assertEquals(true, $route->match("/dummy/toto"));
        self::assertEquals(false, $route->match("/dummy"));
    }

    public function testRegexComplex()
    {
        $route = new Route("/dummy/.+/insert");
        self::assertEquals(false, $route->match("/dummy/3"));
        self::assertEquals(true, $route->match("/dummy/3/insert"));
        self::assertEquals(true, $route->match("/dummy/toto/insert"));
        self::assertEquals(false, $route->match("/dummy"));
    }

    public function testGetArgumentCallback()
    {
        $route = new Route("/dummy/{id}");
        $args = $route->getArguments("/dummy/4", function ($data) {
            return $data + 1;
        });
        self::assertEquals(['id' => "5"], $args);
    }
}