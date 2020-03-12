<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Adapters\MysqlAdapter;
use Zephyrus\Database\Core\Adapters\PostgresqlAdapter;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Pager;

class PagerTest extends TestCase
{
    public function testLimit()
    {
        $req = new Request('http://test.local/3?page=5', 'GET', ['parameters' => ['id' => '3', 'page' => 5]]);
        RequestFactory::set($req);
        $pager = new Pager(1000);
        $max = $pager->getMaxEntitiesPerPage();
        self::assertEquals(Pager::DEFAULT_PAGE_MAX_ENTITIES, $max);
        $limit = $pager->getSqlLimitClause(new MysqlAdapter(['dbms' => 'mysql']));
        self::assertEquals(" LIMIT 200, 50", $limit);
        /*$limit = $pager->getSqlLimitClause(new PostgresqlAdapter(['dbms' => 'pgsql']));
        self::assertEquals(" LIMIT 50 OFFSET 200", $limit);*/
    }

    public function testSimpleDisplay()
    {
        $req = new Request('http://test.local/3', 'GET', ['parameters' => ['id' => '3']]);
        RequestFactory::set($req);
        $pager = new Pager(100);
        $expected = '<div class="pager"><span>1</span><a href="/3?page=2">2</a><a href="/3?page=2">&gt;</a></div>';
        ob_start();
        $pager->display();
        $result = ob_get_clean();
        self::assertEquals($expected, $result);
        self::assertEquals(1, $pager->getCurrentPage());
        self::assertEquals(2, $pager->getMaxPage());
    }

    public function testEmptyPager()
    {
        $req = new Request('http://test.local/3', 'GET', ['parameters' => ['id' => '3']]);
        RequestFactory::set($req);
        $pager = new Pager(0);
        ob_start();
        $pager->display();
        $result = ob_get_clean();
        self::assertEmpty($result);
    }

    public function testValidation()
    {
        $req = new Request('http://test.local/3?page=-1', 'GET', ['parameters' => ['id' => '3', 'page' => -1]]);
        RequestFactory::set($req);
        $pager = new Pager(1000);
        $limit = $pager->getSqlLimitClause(new MysqlAdapter(['dbms' => 'mysql']));
        self::assertEquals(" LIMIT 0, 50", $limit);
    }

    public function testDisplay()
    {
        $req = new Request('http://test.local/3?page=12', 'GET', ['parameters' => ['id' => '3', 'page' => '12']]);
        RequestFactory::set($req);
        $pager = new Pager(1000);
        $expected = '<div class="pager"><a href="/3?page=1">«</a><a href="/3?page=11">&lt;</a><a href="/3?page=8">8</a><a href="/3?page=9">9</a><a href="/3?page=10">10</a><a href="/3?page=11">11</a><span>12</span><a href="/3?page=13">13</a><a href="/3?page=14">14</a><a href="/3?page=15">15</a><a href="/3?page=16">16</a><a href="/3?page=13">&gt;</a><a href="/3?page=20">»</a></div>';
        ob_start();
        $pager->display();
        $result = ob_get_clean();
        self::assertEquals($expected, $result);
    }
}