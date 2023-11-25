<?php namespace Zephyrus\Network\Request;

use PHPUnit\Framework\TestCase;

class QueryStringTest extends TestCase
{
    public function testQueryArguments()
    {
        $baseQuery = "page=4&sort=asc&filters[]=name&filters[]=created_by";
        $queryString = new QueryString($baseQuery);
        $this->assertEquals([
            "page" => 4,
            "sort" => "asc",
            "filters" => ["name", "created_by"]
        ], $queryString->getArguments());
        $this->assertEquals("asc", $queryString->getArgument("sort"));
        $this->assertEquals("created_by", $queryString->getArgument("filters")[1]);
        $this->assertNull($queryString->getArgument("toto"));
        $this->assertEquals("hello", $queryString->getArgument("toto", "hello"));
    }

    public function testRemoveArgument()
    {
        $baseQuery = "page=4&sort=asc&filters[]=name&filters[]=created_by";
        $string = (new QueryString($baseQuery))->removeArgumentEquals("page")->buildString();
        $this->assertEquals("sort=asc&filters[0]=name&filters[1]=created_by", urldecode($string));

        $string = (new QueryString($baseQuery))->removeArgumentEquals("sort")->buildString();

        $this->assertEquals("page=4&filters[0]=name&filters[1]=created_by", urldecode($string));

        $string = (new QueryString($baseQuery))->removeArgumentStartsWith("filt")->buildString();
        $this->assertEquals("page=4&sort=asc", urldecode($string));

        $string = (new QueryString($baseQuery))->removeArgumentEndsWith("ers")->buildString();
        $this->assertEquals("page=4&sort=asc", urldecode($string));
    }
}
