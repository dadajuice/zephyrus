<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Route;

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

    public function testGetArgumentCallback()
    {
        $route = new Route("/dummy/{id}");
        $args = $route->getArguments("/dummy/4", function ($data) {
            return $data + 1;
        });
        self::assertEquals(['id' => "5"], $args);
    }
}