<?php namespace Zephyrus\Tests\Database\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Components\SortParser;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class SortParserTest extends TestCase
{
    public function testBasicSort()
    {
        $request = new Request("http://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'asc']
        ]]);
        RequestFactory::set($request);

        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);
        $parser = new SortParser($filter->getSort());
        self::assertTrue($parser->hasRequested());
        $clause = $parser->buildSqlClause();

        self::assertEquals("ORDER BY name ASC", $clause->getSql());
    }

    public function testNullAscSorting()
    {
        $request = new Request("http://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => ['name' => 'asc']
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);

        $parser = new SortParser($filter->getSort());
        $parser->setAscNullLast(false); // Reverse order of NULLs
        self::assertTrue($parser->hasRequested());
        $clause = $parser->buildSqlClause();
        self::assertEquals("ORDER BY name ASC NULLS FIRST", $clause->getSql());
    }

    public function testNullDescSorting()
    {
        $request = new Request("http://example.com?sorts[name]=desc", "get", ['parameters' => [
            'sorts' => ['name' => 'desc']
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);
        $parser = new SortParser($filter->getSort());

        $parser->setDescNullLast(true); // Reverse order of NULLs
        self::assertTrue($parser->hasRequested());
        $clause = $parser->buildSqlClause();
        self::assertEquals("ORDER BY name DESC NULLS LAST", $clause->getSql());
    }

    public function testDefaultSort()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);
        $filter->getSort()->setDefaultSorts(['name' => 'desc', 'price' => 'asc']);
        $parser = new SortParser($filter->getSort());

        self::assertTrue($parser->hasRequested());
        self::assertFalse($filter->getSort()->isDefined());
        $clause = $parser->buildSqlClause();
        self::assertEquals("ORDER BY name DESC, price ASC", $clause->getSql());
    }

    public function testNothingAllowed()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields([]);
        $filter->getSort()->setDefaultSorts(['name' => 'desc', 'price' => 'asc']);

        $parser = new SortParser($filter->getSort());
        $clause = $parser->buildSqlClause();
        self::assertEquals("", $clause->getSql());
    }

    public function testNoDefault()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $parser = new SortParser($filter->getSort());
        $clause = $parser->buildSqlClause();
        self::assertEquals("", $clause->getSql());
    }

    public function testCombinedSort()
    {
        $request = new Request("http://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => [
                'name' => 'asc',
                'price' => 'desc',
                'brand' => 'asc'
            ]
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);
        $parser = new SortParser($filter->getSort());
        $clause = $parser->buildSqlClause();

        self::assertEquals("ORDER BY name ASC, price DESC, brand ASC", $clause->getSql());
    }

    public function testCombinedSortWithConversion()
    {
        $request = new Request("http://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => [
                'name' => 'asc',
                'price' => 'desc',
                'brand' => 'asc'
            ]
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);
        $parser = new SortParser($filter->getSort());
        $parser->setAliasColumns([
            'price' => 'amount'
        ]);
        $clause = $parser->buildSqlClause();

        self::assertEquals("ORDER BY name ASC, amount DESC, brand ASC", $clause->getSql());
    }

    public function testSortWithNonAllowedColumn()
    {
        $request = new Request("http://example.com?sorts[name]=asc", "get", ['parameters' => [
            'sorts' => [
                'name' => 'asc',
                'price' => 'desc',
                'brand' => 'asc'
            ]
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'brand']);
        $parser = new SortParser($filter->getSort());
        $clause = $parser->buildSqlClause();

        // Skips the price since its not allowed
        self::assertEquals("ORDER BY name ASC, brand ASC", $clause->getSql());
    }

    public function testInvalidOrder()
    {
        $request = new Request("http://example.com?sorts[brand]=toto", "get", ['parameters' => [
            'sorts' => ['brand' => 'toto']
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getSort()->setAllowedFields(['name', 'price', 'brand']);
        $parser = new SortParser($filter->getSort());
        $clause = $parser->buildSqlClause();

        self::assertEquals("ORDER BY brand ASC", $clause->getSql());
    }
}
