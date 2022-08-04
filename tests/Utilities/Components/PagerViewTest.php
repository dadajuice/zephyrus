<?php namespace Zephyrus\Tests\Utilities\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Components\PagerParser;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Components\PagerView;

class PagerViewTest extends TestCase
{
    public function testDefaultEmpty()
    {
        $request = new Request("http://example.com/projects", "get", ['parameters' => []]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();
        $view = new PagerView($parser, 0);
        $html = $view->getHtml();
        self::assertEmpty($html);
    }

    public function testFirstOfThreePagesOnBuffer()
    {
        $request = new Request("http://example.com/projects?page=1", "get", ['parameters' => [
            'page' => 1
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();
        $view = new PagerView($parser, 150); // 3 pages ...
        ob_start();
        echo $view;
        $html = ob_get_clean();
        self::assertEquals('<div class="pager"><span>1</span><a href="/projects?page=2">2</a><a href="/projects?page=3">3</a><a href="/projects?page=2">&gt;</a></div>', $html);
    }

    public function testFirstOfThreePagesOnDisplay()
    {
        $request = new Request("http://example.com/projects?page=1", "get", ['parameters' => [
            'page' => 1
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();

        $view = new PagerView($parser, 150); // 3 pages ...
        ob_start();
        $view->display();
        $html = ob_get_clean();
        self::assertEquals('<div class="pager"><span>1</span><a href="/projects?page=2">2</a><a href="/projects?page=3">3</a><a href="/projects?page=2">&gt;</a></div>', $html);
    }

    public function testFirstOfThreePages()
    {
        $request = new Request("http://example.com/projects?page=1", "get", ['parameters' => [
            'page' => 1
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();

        $view = new PagerView($parser, 150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><span>1</span><a href="/projects?page=2">2</a><a href="/projects?page=3">3</a><a href="/projects?page=2">&gt;</a></div>', $html);
    }

    public function testSecondOfThreePages()
    {
        $request = new Request("http://example.com/projects?page=2", "get", ['parameters' => [
            'page' => 2
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();

        $view = new PagerView($parser, 150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="/projects?page=1">&lt;</a><a href="/projects?page=1">1</a><span>2</span><a href="/projects?page=3">3</a><a href="/projects?page=3">&gt;</a></div>', $html);
    }

    public function testDisplayFirstOfSixteenPages()
    {
        $request = new Request("http://example.com/projects?page=2", "get", ['parameters' => [
            'page' => 2
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();
        $view = new PagerView($parser, 760); // 16 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="/projects?page=1">&lt;</a><a href="/projects?page=1">1</a><span>2</span><a href="/projects?page=3">3</a><a href="/projects?page=4">4</a><a href="/projects?page=5">5</a><a href="/projects?page=6">6</a><a href="/projects?page=7">7</a><a href="/projects?page=8">8</a><a href="/projects?page=9">9</a><a href="/projects?page=3">&gt;</a><a href="/projects?page=16">»</a></div>', $html);
    }

    public function testDisplayFirstOfSixteenPagesValidated()
    {
        $request = new Request("http://example.com/projects?page=900", "get", ['parameters' => [
            'page' => 900
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();

        $view = new PagerView($parser, 760); // 16 pages ...
        $html = $view->getHtml(); // Will go to page one, since page 900 doesn't exist.
        self::assertEquals('<div class="pager"><span>1</span><a href="/projects?page=2">2</a><a href="/projects?page=3">3</a><a href="/projects?page=4">4</a><a href="/projects?page=5">5</a><a href="/projects?page=6">6</a><a href="/projects?page=7">7</a><a href="/projects?page=8">8</a><a href="/projects?page=9">9</a><a href="/projects?page=2">&gt;</a><a href="/projects?page=16">»</a></div>', $html);
    }

    public function testDisplayTenthOfSixteenPages()
    {
        $request = new Request("http://example.com/projects?page=10", "get", ['parameters' => [
            'page' => 10
        ]]);
        RequestFactory::set($request);

        $parser = new PagerParser();
        $parser->parse();
        $view = new PagerView($parser, 760); // 16 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="/projects?page=1">«</a><a href="/projects?page=9">&lt;</a><a href="/projects?page=6">6</a><a href="/projects?page=7">7</a><a href="/projects?page=8">8</a><a href="/projects?page=9">9</a><span>10</span><a href="/projects?page=11">11</a><a href="/projects?page=12">12</a><a href="/projects?page=13">13</a><a href="/projects?page=14">14</a><a href="/projects?page=11">&gt;</a><a href="/projects?page=16">»</a></div>', $html);
    }

    public function testWithPreExistingQueryString()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc&archived=true&filters[price:contains]=56", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        $parser->parse();
        $view = new PagerView($parser, 150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><span>1</span><a href="/projects?page=2&sorts[name]=asc&archived=true&filters[price:contains]=56">2</a><a href="/projects?page=3&sorts[name]=asc&archived=true&filters[price:contains]=56">3</a><a href="/projects?page=2&sorts[name]=asc&archived=true&filters[price:contains]=56">&gt;</a></div>', $html);
    }

    public function testWithPreExistingPageQueryString()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc&archived=true&page=2&filters[price:contains]=56", "get", ['parameters' => [
            'page' => 2
        ]]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        $parser->parse();
        $view = new PagerView($parser, 150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="/projects?page=1&sorts[name]=asc&archived=true&filters[price:contains]=56">&lt;</a><a href="/projects?page=1&sorts[name]=asc&archived=true&filters[price:contains]=56">1</a><span>2</span><a href="/projects?page=3&sorts[name]=asc&archived=true&filters[price:contains]=56">3</a><a href="/projects?page=3&sorts[name]=asc&archived=true&filters[price:contains]=56">&gt;</a></div>', $html);
    }
}
