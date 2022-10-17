<?php namespace Zephyrus\Tests\Database\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Components\PagerParser;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class PagerParserTest extends TestCase
{
    public function testDefaultPager()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $parser = new PagerParser($filter->getPagination());
        self::assertFalse($parser->hasRequested());
        $clause = $parser->buildSqlClause();
        self::assertEquals("LIMIT 50", $clause->getSql());
    }

    public function testPage()
    {
        $request = new Request("http://example.com/projects?page=5", "get", ['parameters' => [
            'page' => 5
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $parser = new PagerParser($filter->getPagination());
        self::assertTrue($parser->hasRequested());
        $clause = $parser->buildSqlClause();
        self::assertEquals("LIMIT 50 OFFSET 200", $clause->getSql());
    }

    public function testPageLimit()
    {
        $request = new Request("http://example.com/projects?page=5&limit=100", "get", ['parameters' => [
            'page' => 5,
            'limit' => 100
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getPagination()->setDefaultLimit(50, 250);
        $parser = new PagerParser($filter->getPagination());
        $clause = $parser->buildSqlClause();
        self::assertEquals("LIMIT 100 OFFSET 400", $clause->getSql());
    }

    public function testCustomMaxPage()
    {
        $request = new Request("http://example.com/projects?page=5", "get", ['parameters' => [
            'page' => 2
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $filter->getPagination()->setDefaultLimit(80, 120);
        $parser = new PagerParser($filter->getPagination());
        self::assertTrue($parser->hasRequested());
        $clause = $parser->buildSqlClause();
        self::assertEquals("LIMIT 80 OFFSET 80", $clause->getSql());
    }

    public function testRejectedLimitPage()
    {
        $request = new Request("http://example.com/projects?page=2&limit=500", "get", ['parameters' => [
            'page' => 2, 'limit' => 500
        ]]);
        RequestFactory::set($request);
        $filter = RequestFactory::read()->getFilter();
        $parser = new PagerParser($filter->getPagination());
        self::assertTrue($parser->hasRequested());
        $clause = $parser->buildSqlClause();
        self::assertEquals("LIMIT 50 OFFSET 50", $clause->getSql());
    }
}
