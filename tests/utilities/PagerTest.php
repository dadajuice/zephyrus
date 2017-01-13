<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Pager;

class PagerTest extends TestCase
{
    public function testLimit()
    {
        $req = new Request('http://test.local/3?page=5', 'GET', ['id' => '3', 'page' => 5]);
        RequestFactory::set($req);
        $pager = new Pager(1000);
        $max = $pager->getMaxEntitiesPerPage();
        self::assertEquals(Pager::PAGE_MAX_ENTITIES, $max);
        $limit = $pager->getSqlLimit();
        self::assertEquals(" LIMIT 200, 50", $limit);
    }

    public function testDisplay()
    {
        $req = new Request('http://test.local/3?page=120', 'GET', ['id' => '3', 'page' => 5]);
        RequestFactory::set($req);
        $pager = new Pager(1000);
        $expected = '<div class="pager"><a href="/3?page=4">&lt;</a><a href="/3?page=1">1</a><a href="/3?page=2">2</a><a href="/3?page=3">3</a><a href="/3?page=4">4</a><span>5</span><a href="/3?page=6">6</a><a href="/3?page=7">7</a><a href="/3?page=8">8</a><a href="/3?page=9">9</a><a href="/3?page=6">&gt;</a><a href="/3?page=20">»</a></div>';
        ob_start();
        $pager->display();
        $result = ob_get_clean();
        self::assertEquals($expected, $result);
    }
}