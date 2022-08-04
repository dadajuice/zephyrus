<?php namespace Zephyrus\Tests\Utilities\Components;

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
        $parser = new PagerParser();
        self::assertFalse($parser->hasRequested());
        $model = $parser->parse();
        $clause = $model->buildLimitClause();
        self::assertEquals("LIMIT 50 OFFSET 0", $clause->getSql());
    }

    public function testPage()
    {
        $request = new Request("http://example.com/projects?page=5", "get", ['parameters' => [
            'page' => 5
        ]]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        self::assertTrue($parser->hasRequested());
        $model = $parser->parse();
        $clause = $model->buildLimitClause();
        self::assertEquals("LIMIT 50 OFFSET 200", $clause->getSql());
    }

    public function testPageLimit()
    {
        $request = new Request("http://example.com/projects?page=5&limit=100", "get", ['parameters' => [
            'page' => 5,
            'limit' => 100
        ]]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        $parser->setMaxLimitAllowed(250);
        $model = $parser->parse();
        $clause = $model->buildLimitClause();
        self::assertEquals("LIMIT 100 OFFSET 400", $clause->getSql());
    }

    public function testCustomMaxPage()
    {
        $request = new Request("http://example.com/projects?page=5", "get", ['parameters' => [
            'page' => 2
        ]]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        $parser->setMaxLimitAllowed(120);
        $parser->setDefaultLimit(80);
        self::assertTrue($parser->hasRequested());
        $model = $parser->parse();
        $clause = $model->buildLimitClause();
        self::assertEquals("LIMIT 80 OFFSET 80", $clause->getSql());
    }

    public function testRejectedLimitPage()
    {
        $request = new Request("http://example.com/projects?page=2&limit=500", "get", ['parameters' => [
            'page' => 2, 'limit' => 500
        ]]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        self::assertTrue($parser->hasRequested());
        $model = $parser->parse();
        $clause = $model->buildLimitClause();
        self::assertEquals("LIMIT 50 OFFSET 50", $clause->getSql());
    }
}
