<?php namespace Zephyrus\Network\Router;

use PHPUnit\Framework\TestCase;

class RouteDefinitionTest extends TestCase
{
    public function testGetSimpleArgument()
    {
        $route = new RouteDefinition("/dummy/{id}");
        $route->extractArgumentsFromUrl("/dummy/4");
        $this->assertEquals('/dummy/{id}', $route->getRoute());
        $this->assertEquals(['id' => "4"], $route->getArguments());
    }

    public function testGetComplexArgument()
    {
        $route = new RouteDefinition("/dummy/{id}/{fish}");
        $route->extractArgumentsFromUrl("/dummy/4/goldfish");
        $this->assertEquals([
            'id' => "4",
            'fish' => "goldfish"
        ], $route->getArguments());
    }

    public function testRegexMatching()
    {
        $route = new RouteDefinition("/dummy/.+");
        $this->assertTrue($route->matchUrl("/dummy/3"));
        $this->assertTrue($route->matchUrl("/dummy/3/info"));
        $this->assertTrue($route->matchUrl("/dummy/toto"));
        $this->assertFalse($route->matchUrl("/dummy"));
    }

    public function testRegexComplex()
    {
        $route = new RouteDefinition("/dummy/.+/insert");
        $this->assertFalse($route->matchUrl("/dummy/3"));
        $this->assertTrue($route->matchUrl("/dummy/3/insert"));
        $this->assertTrue($route->matchUrl("/dummy/toto/insert"));
        $this->assertFalse($route->matchUrl("/dummy"));
    }
}
