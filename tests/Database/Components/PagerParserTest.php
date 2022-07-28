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
        $parser = new PagerParser();
        self::assertFalse($parser->hasRequested());
        $clause = $parser->parse();
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
        $clause = $parser->parse();
        self::assertEquals("LIMIT 50 OFFSET 200", $clause->getSql());
    }
}
