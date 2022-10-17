<?php namespace Zephyrus\Tests\Utilities\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Utilities\Components\FilterConfiguration;
use Zephyrus\Utilities\Components\ListFilter;

class ListFilterTest extends TestCase
{
    public function testSort()
    {
        $request = new Request("https://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'asc']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $sort = $filter->getSort();
        self::assertEquals(['name' => 'asc'], $sort->getSorts());
        self::assertTrue($sort->isDefined());
        self::assertEquals("sorts", $sort->getSortParameterName());
    }

    public function testDefaultSort()
    {
        $request = new Request("https://example.com", "get", ['parameters' => []]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $sort = $filter->getSort();
        $sort->setDefaultSorts(['date' => 'desc']);
        self::assertEquals(['date' => 'desc'], $sort->getSorts());
        self::assertFalse($sort->isDefined());
    }

    public function testEmptySort()
    {
        $request = new Request("https://example.com", "get", ['parameters' => []]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $sort = $filter->getSort();
        self::assertEquals([], $sort->getSorts());
        self::assertFalse($sort->isDefined());
    }

    public function testAllowedSort()
    {
        $request = new Request("https://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'asc']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $sort = $filter->getSort();
        $sort->setAllowedFields(['name', 'date']);
        self::assertEquals(['name' => 'asc'], $sort->getSorts());
        self::assertTrue($sort->isDefined());
    }

    public function testNotAllowedSort()
    {
        $request = new Request("https://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'asc']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $sort = $filter->getSort();
        $sort->setAllowedFields(['date']);
        self::assertEquals([], $sort->getSorts());
    }

    public function testFunnel()
    {
        $request = new Request("https://example.com?filters[name:contains]=bob", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $funnel = $filter->getFunnel();
        self::assertEquals(['name:contains' => 'bob'], $funnel->getFilters());
        self::assertTrue($funnel->isDefined());
        self::assertEquals("filters", $funnel->getFilterParameterName());
        self::assertEquals("search", $funnel->getSearchParameterName());
    }

    public function testAllowedFunnel()
    {
        $request = new Request("https://example.com?filters[name:contains]=bob", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $funnel = $filter->getFunnel();
        $funnel->setAllowedFields(['name', 'date']);
        self::assertEquals(['name:contains' => 'bob'], $funnel->getFilters());
        self::assertTrue($funnel->isDefined());
    }

    public function testNotAllowedFunnel()
    {
        $request = new Request("https://example.com?filters[name:contains]=bob", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $funnel = $filter->getFunnel();
        $funnel->setAllowedFields(['date']);
        self::assertEquals([], $funnel->getFilters());
    }

    public function testInvalidFunnel()
    {
        $request = new Request("https://example.com?filters[name:jhsafkjhsdf]=bob", "get", ['parameters' => [
            'filters' => ['name:jhsafkjhsdf' => 'bob']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $funnel = $filter->getFunnel();
        $funnel->setAllowedFields(['name']);
        self::assertEquals([], $funnel->getFilters());
    }

    public function testPagination()
    {
        $request = new Request("https://example.com?page=8&limit=200", "get", ['parameters' => [
            'page' => 8,
            'limit' => 200
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $pagination = $filter->getPagination();
        self::assertEquals(8, $pagination->getCurrentPage());
        self::assertEquals(50, $pagination->getLimit());

    }

    public function testPaginationWithLimit()
    {
        $request = new Request("https://example.com?page=8&limit=200", "get", ['parameters' => [
            'page' => 8,
            'limit' => 200
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $pagination = $filter->getPagination();
        $pagination->setDefaultLimit(50, 200);
        self::assertEquals(8, $pagination->getCurrentPage());
        self::assertEquals(200, $pagination->getLimit());
    }

    public function testPaginationWithLimit2()
    {
        $request = new Request("https://example.com?page=8&limit=500", "get", ['parameters' => [
            'page' => 8,
            'limit' => 500
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $pagination = $filter->getPagination();
        $pagination->setDefaultLimit(100, 200);
        self::assertEquals(5, $pagination->getMaxPage(1000));
        self::assertEquals(8, $pagination->getCurrentPage());
        self::assertEquals(200, $pagination->getLimit());
    }

    public function testPaginationWithLimit3()
    {
        $request = new Request("https://example.com?page=8", "get", ['parameters' => [
            'page' => 8
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $pagination = $filter->getPagination();
        $pagination->setDefaultLimit(100, 200);
        self::assertEquals(10, $pagination->getMaxPage(1000));
        self::assertEquals(8, $pagination->getCurrentPage());
        self::assertEquals(100, $pagination->getLimit());
    }

    public function testInvalidPagination()
    {
        $request = new Request("https://example.com?page=8", "get", ['parameters' => [
            'page' => "errr"
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $pagination = $filter->getPagination();
        self::assertEquals(1, $pagination->getCurrentPage());
    }

    public function testInvalid2Pagination()
    {
        $request = new Request("https://example.com?page=8", "get", ['parameters' => [
            'page' => -3
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $pagination = $filter->getPagination();
        self::assertEquals(1, $pagination->getCurrentPage());
    }
}
