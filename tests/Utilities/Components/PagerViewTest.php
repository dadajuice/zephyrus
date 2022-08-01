<?php namespace Zephyrus\Tests\Utilities\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Components\PagerModel;
use Zephyrus\Utilities\Components\PagerParser;
use Zephyrus\Utilities\Components\PagerView;

class PagerViewTest extends TestCase
{
    public function testDefaultEmpty()
    {
        $model = new PagerModel(50, 0);
        $view = new PagerView($model, 0);
        $html = $view->getHtml();
        self::assertEmpty($html);
    }

    public function testFirstOfThreePagesOnBuffer()
    {
        $model = new PagerModel(50, 0);
        $view = new PagerView($model, 150); // 3 pages ...
        ob_start();
        echo $view;
        $html = ob_get_clean();
        self::assertEquals('<div class="pager"><span>1</span><a href="#?page=2">2</a><a href="#?page=3">3</a><a href="#?page=2">&gt;</a></div>', $html);
    }

    public function testFirstOfThreePagesOnDisplay()
    {
        $model = new PagerModel(50, 0);
        $view = new PagerView($model, 150); // 3 pages ...
        ob_start();
        $view->display();
        $html = ob_get_clean();
        self::assertEquals('<div class="pager"><span>1</span><a href="#?page=2">2</a><a href="#?page=3">3</a><a href="#?page=2">&gt;</a></div>', $html);
    }

    public function testFirstOfThreePages()
    {
        $model = new PagerModel(50, 0);
        $view = new PagerView($model, 150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><span>1</span><a href="#?page=2">2</a><a href="#?page=3">3</a><a href="#?page=2">&gt;</a></div>', $html);
    }

    public function testSecondOfThreePages()
    {
        $model = new PagerModel(50, 50);
        $model->setCurrentPage(2);
        $view = new PagerView($model, 150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="#?page=1">&lt;</a><a href="#?page=1">1</a><span>2</span><a href="#?page=3">3</a><a href="#?page=3">&gt;</a></div>', $html);
    }

    public function testDisplayFirstOfSixteenPages()
    {
        $model = new PagerModel(50, 50);
        $model->setCurrentPage(2);
        $view = new PagerView($model, 760); // 16 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="#?page=1">&lt;</a><a href="#?page=1">1</a><span>2</span><a href="#?page=3">3</a><a href="#?page=4">4</a><a href="#?page=5">5</a><a href="#?page=6">6</a><a href="#?page=7">7</a><a href="#?page=8">8</a><a href="#?page=9">9</a><a href="#?page=3">&gt;</a><a href="#?page=16">»</a></div>', $html);
    }

    public function testDisplayFirstOfSixteenPagesValidated()
    {
        $model = new PagerModel(50, 50);
        $model->setCurrentPage(900);
        $view = new PagerView($model, 760); // 16 pages ...
        $html = $view->getHtml(); // Will go to page one, since page 900 doesn't exist.
        self::assertEquals('<div class="pager"><span>1</span><a href="#?page=2">2</a><a href="#?page=3">3</a><a href="#?page=4">4</a><a href="#?page=5">5</a><a href="#?page=6">6</a><a href="#?page=7">7</a><a href="#?page=8">8</a><a href="#?page=9">9</a><a href="#?page=2">&gt;</a><a href="#?page=16">»</a></div>', $html);
    }

    public function testDisplayTenthOfSixteenPages()
    {
        $model = new PagerModel(50, 50);
        $model->setCurrentPage(10);
        $view = new PagerView($model, 760); // 16 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="#?page=1">«</a><a href="#?page=9">&lt;</a><a href="#?page=6">6</a><a href="#?page=7">7</a><a href="#?page=8">8</a><a href="#?page=9">9</a><span>10</span><a href="#?page=11">11</a><a href="#?page=12">12</a><a href="#?page=13">13</a><a href="#?page=14">14</a><a href="#?page=11">&gt;</a><a href="#?page=16">»</a></div>', $html);
    }

    public function testWithPreExistingQueryString()
    {
        $request = new Request("http://example.com/projects?sorts[name]=asc&archived=true&filters[price:contains]=56", "get", ['parameters' => []]);
        RequestFactory::set($request);
        $parser = new PagerParser();
        $model = $parser->parse();
        $view = $model->buildView(150); // 3 pages ...
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
        $model = $parser->parse();
        $view = $model->buildView(150); // 3 pages ...
        $html = $view->getHtml();
        self::assertEquals('<div class="pager"><a href="/projects?page=1&sorts[name]=asc&archived=true&filters[price:contains]=56">&lt;</a><a href="/projects?page=1&sorts[name]=asc&archived=true&filters[price:contains]=56">1</a><span>2</span><a href="/projects?page=3&sorts[name]=asc&archived=true&filters[price:contains]=56">3</a><a href="/projects?page=3&sorts[name]=asc&archived=true&filters[price:contains]=56">&gt;</a></div>', $html);
    }
}
