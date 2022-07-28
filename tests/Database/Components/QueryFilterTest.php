<?php namespace Zephyrus\Tests\Database\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Components\QueryFilter;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class QueryFilterTest extends TestCase
{
    public function testNothingSpecific()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = new QueryFilter();
        self::assertTrue($filter->hasPagination()); // Default there is always pager ...
        self::assertFalse($filter->hasSort());
        self::assertFalse($filter->hasFilter());
    }

    public function testPagination()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => [
            'page' => 3
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter();
        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->paginate($query);
        self::assertEquals("SELECT * FROM view_project LIMIT 50 OFFSET 100", $resultQuery);
    }
}
