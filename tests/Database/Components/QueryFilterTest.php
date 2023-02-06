<?php namespace Zephyrus\Tests\Database\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Components\QueryFilter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseConfiguration;
use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class QueryFilterTest extends TestCase
{
    public function testNothingSpecific()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertFalse($filter->isPaginationRequested());
        self::assertFalse($filter->isSortRequested());
        self::assertFalse($filter->isFilterRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project", $resultQuery);

        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project", $resultQuery);

        $resultQuery = $filter->paginate($query, false);
        self::assertEquals("SELECT * FROM view_project", $resultQuery);
    }

    public function testPagination()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => [
            'page' => 3
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isPaginationRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->paginate($query);
        self::assertEquals("SELECT * FROM view_project LIMIT 50 OFFSET 100", $resultQuery);
    }

    public function testForcePagination()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertFalse($filter->isPaginationRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->paginate($query, true); // Default
        self::assertEquals("SELECT * FROM view_project LIMIT 50", $resultQuery);
    }

    public function testSort()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isSortRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project ORDER BY name DESC", $resultQuery);
    }

    public function testSortWithNestedQuery()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isSortRequested());

        $query = "SELECT * FROM view_project WHERE client_id IN (SELECT client_id FROM client) AND brand = ?";
        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project WHERE client_id IN (SELECT client_id FROM client) AND brand = ? ORDER BY name DESC", $resultQuery);
    }

    public function testSortWithNestedQuery2()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isSortRequested());

        $query = "SELECT * FROM view_project WHERE client_id IN (SELECT client_id FROM client WHERE contact = ?) AND brand = ?";
        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project WHERE client_id IN (SELECT client_id FROM client WHERE contact = ?) AND brand = ? ORDER BY name DESC", $resultQuery);
    }

    public function testSortWithNestedLimitQuery()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isSortRequested());

        $query = "SELECT * FROM view_project WHERE client_id IN (SELECT client_id FROM client LIMIT 5) AND brand = ?";
        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project WHERE client_id IN (SELECT client_id FROM client LIMIT 5) AND brand = ? ORDER BY name DESC", $resultQuery);
    }

    public function testSortWithLimit()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isSortRequested());

        $query = "SELECT * FROM view_project LIMIT 5";
        $resultQuery = $filter->sort($query);
        self::assertEquals("SELECT * FROM view_project ORDER BY name DESC LIMIT 5", $resultQuery);
    }

    public function testSimpleFilter()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT * FROM view_project";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project WHERE (name ILIKE ?)", $resultQuery);
    }

    public function testFilterWithPreExistingWhere()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro&filters[age:equals]=18", "get", ['parameters' => [
            'filters' => [
                'name:contains' => 'micro',
                'age:equals' => '18'
            ]
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        $filter->getFilterParser()->setAggregateOperator(WhereClause::OPERATOR_OR);
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT * FROM view_project WHERE state = 'ACTIVE' AND archive_date IS NULL";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project WHERE state = 'ACTIVE' AND archive_date IS NULL AND ((name ILIKE ?) OR (age = ?))", $resultQuery);

    }

    public function testFilterWithPreExistingNestedWhere()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT * FROM view_project WHERE state = 'ACTIVE' AND (archive_date IS NULL OR project_id IN (SELECT id FROM test WHERE test_id = view_project.iterator))";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT * FROM view_project WHERE state = 'ACTIVE' AND (archive_date IS NULL OR project_id IN (SELECT id FROM test WHERE test_id = view_project.iterator)) AND ((name ILIKE ?))", $resultQuery);
    }

    public function testFilterWithHaving()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT customer_id, SUM(amount) FROM payment GROUP BY customer_id HAVING SUM (amount) > 200";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT customer_id, SUM(amount) FROM payment WHERE (name ILIKE ?) GROUP BY customer_id HAVING SUM (amount) > 200", $resultQuery);
    }

    public function testFilterWithHavingWithoutGroupBy()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT customer_id, SUM(amount) FROM payment HAVING SUM (amount) > 200";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT customer_id, SUM(amount) FROM payment WHERE (name ILIKE ?) HAVING SUM (amount) > 200", $resultQuery);
    }

    public function testFilterWithHavingAndWhere()
    {
        $request = new Request("http://example.com/projects?filters[name:contains]=micro", "get", ['parameters' => [
            'filters' => ['name:contains' => 'micro']
        ]]);
        RequestFactory::set($request);
        $filter = new QueryFilter(RequestFactory::read()->getFilter());
        self::assertTrue($filter->isFilterRequested());

        $query = "SELECT customer_id, SUM(amount) FROM payment WHERE client_id = ? GROUP BY customer_id HAVING SUM (amount) > 200";
        $resultQuery = $filter->filter($query);
        self::assertEquals("SELECT customer_id, SUM(amount) FROM payment WHERE client_id = ? AND ((name ILIKE ?)) GROUP BY customer_id HAVING SUM (amount) > 200", $resultQuery);
    }
}
