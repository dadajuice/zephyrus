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

        $parser = new SortParser();
        self::assertTrue($parser->hasRequested());
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("ORDER BY name ASC", $clause->getSql());
    }

    public function testDefaultSort()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);

        $parser = new SortParser();
        $parser->setDefaultSort(['name' => 'desc', 'price' => 'asc']);
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        self::assertFalse($parser->hasRequested());
        $clause = $parser->parse();

        self::assertEquals("ORDER BY name DESC, price ASC", $clause->getSql());
    }

    public function testNothingAllowed()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $parser = new SortParser();
        $parser->setDefaultSort(['name' => 'desc', 'price' => 'asc']);
        $clause = $parser->parse();
        self::assertEquals("", $clause->getSql());
    }

    public function testNoDefault()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $parser = new SortParser();
        $clause = $parser->parse();
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

        $parser = new SortParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

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

        $parser = new SortParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $parser->setAliasColumns([
            'price' => 'amount'
        ]);
        $clause = $parser->parse();

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

        $parser = new SortParser();
        $parser->setAllowedColumns(['name', 'brand']);
        $clause = $parser->parse();

        // Skips the price since its not allowed
        self::assertEquals("ORDER BY name ASC, brand ASC", $clause->getSql());
    }

    public function testInvalidOrder()
    {
        $request = new Request("http://example.com?sorts[brand]=toto", "get", ['parameters' => [
            'sorts' => ['brand' => 'toto']
        ]]);
        RequestFactory::set($request);

        $parser = new SortParser();
        $parser->setAllowedColumns(['name', 'price', 'brand']);
        $clause = $parser->parse();

        self::assertEquals("ORDER BY brand ASC", $clause->getSql());
    }
}
