<?php namespace Zephyrus\Tests\Database\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Components\FilterParser;
use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class FilterParserTest extends TestCase
{
    public function testBasicFilter()
    {
        $request = new Request("http://example.com?filters[name:contains]=bob", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        self::assertTrue($parser->hasRequested());
        $clause = $parser->parse();

        self::assertEquals("WHERE (name ILIKE ?)", $clause->getSql());
        self::assertEquals(["%bob%"], $clause->getQueryParameters());
    }

    public function testNoFilters()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertFalse($parser->hasRequested());
        self::assertEquals("", $clause->getSql());
    }

    public function testAliasColumn()
    {
        $request = new Request("http://example.com?filters[name:contains]=bob", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $parser->setAliasColumns(['name' => 'title']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (title ILIKE ?)", $clause->getSql());
        self::assertEquals(["%bob%"], $clause->getQueryParameters());
    }

    public function testCombinedFilter()
    {
        $request = new Request("http://example.com?filters[name:contains]=bob&filters[brand:ends]=soft", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob', 'brand:ends' => 'soft']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name ILIKE ?) OR (brand ILIKE ?)", $clause->getSql());
        self::assertEquals(["%bob%", "%soft"], $clause->getQueryParameters());
    }

    public function testCombinedAndFilter()
    {
        $request = new Request("http://example.com?filters[name:contains]=bob&filters[brand:ends]=soft", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob', 'brand:ends' => 'soft']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $parser->setAggregateOperator(WhereClause::OPERATOR_AND);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name ILIKE ?) AND (brand ILIKE ?)", $clause->getSql());
        self::assertEquals(["%bob%", "%soft"], $clause->getQueryParameters());
    }

    public function testFilterBegins()
    {
        $request = new Request("http://example.com?filters[name:begins]=bob", "get", ['parameters' => [
            'filters' => ['name:begins' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name ILIKE ?)", $clause->getSql());
        self::assertEquals(["bob%"], $clause->getQueryParameters());
    }

    public function testFilterEnds()
    {
        $request = new Request("http://example.com?filters[name:ends]=bob", "get", ['parameters' => [
            'filters' => ['name:ends' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name ILIKE ?)", $clause->getSql());
        self::assertEquals(["%bob"], $clause->getQueryParameters());
    }

    public function testFilterEquals()
    {
        $request = new Request("http://example.com?filters[name:equals]=bob", "get", ['parameters' => [
            'filters' => ['name:equals' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name = ?)", $clause->getSql());
        self::assertEquals(["bob"], $clause->getQueryParameters());
    }

    public function testDefaultContains()
    {
        $request = new Request("http://example.com?filters[name]=bob", "get", ['parameters' => [
            'filters' => ['name' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name ILIKE ?)", $clause->getSql());
        self::assertEquals(["%bob%"], $clause->getQueryParameters());
    }

    public function testNothingAllowed()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $parser = new FilterParser();
        $clause = $parser->parse();
        self::assertEquals("", $clause->getSql());
    }

    public function testIgnoreInvalidQualifier()
    {
        $request = new Request("http://example.com?filters[name:kjhsdfkhsf]=bob", "get", ['parameters' => [
            'filters' => ['name:kjhsdfkhsf' => 'bob']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("", $clause->getSql());
    }

    public function testExtraFieldNotAllowed()
    {
        $request = new Request("http://example.com?filters[name:equals]=bob&filters[username:equals]=toto", "get", ['parameters' => [
            'filters' => ['name:equals' => 'bob', 'username:equals' => 'toto']
        ]]);
        RequestFactory::set($request);

        $parser = new FilterParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("WHERE (name = ?)", $clause->getSql());
        self::assertEquals(["bob"], $clause->getQueryParameters());
    }
}
