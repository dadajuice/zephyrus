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
        self::assertFalse($filter->isPaginationRequested());
        self::assertFalse($filter->isSortRequested());
        self::assertFalse($filter->isFilterRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project", $resultQuery);

        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project", $resultQuery);
    }

    public function testPagination()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => [
            'page' => 3
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter();
        self::assertTrue($filter->isPaginationRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->paginate($query);
        self::assertEquals("SELECT * FROM view_project LIMIT 50 OFFSET 100", $resultQuery);
    }

    public function testSort()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter();
        $filter->setAllowedSortColumns(['name']);
        self::assertTrue($filter->isSortRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project ORDER BY name DESC", $resultQuery);
    }

    public function testSimpleFilter()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter();
        $filter->setAllowedFilterColumns(['name']);
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project WHERE (name ILIKE ?)", $resultQuery);
    }

    public function testFilterWithPreExistingWhere()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter();
        $filter->setAllowedFilterColumns(['name']);
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT * FROM view_project WHERE state = 'ACTIVE' AND archive_date IS NULL";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project WHERE state = 'ACTIVE' AND archive_date IS NULL AND (name ILIKE ?)", $resultQuery);
    }
}
