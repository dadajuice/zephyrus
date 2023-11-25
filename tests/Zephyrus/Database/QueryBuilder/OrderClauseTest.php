<?php namespace Zephyrus\Tests\Database\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\QueryBuilder\OrderByClause;

class OrderClauseTest extends TestCase
{
    public function testAsc()
    {
        $order = new OrderByClause();
        $order->asc('username');
        self::assertEquals("ORDER BY username ASC", $order->getSql());
    }

    public function testDesc()
    {
        $order = new OrderByClause();
        $order->desc('lastname');
        self::assertEquals("ORDER BY lastname DESC", $order->getSql());
    }

    public function testAscNulls()
    {
        $order = new OrderByClause();
        $order->asc('username', false);
        self::assertEquals("ORDER BY username ASC NULLS FIRST", $order->getSql());
    }

    public function testDescNulls()
    {
        $order = new OrderByClause();
        $order->desc('lastname', false);
        self::assertEquals("ORDER BY lastname DESC NULLS LAST", $order->getSql());
    }

    public function testCombined()
    {
        $order = new OrderByClause();
        $order
            ->asc('username')
            ->asc('firstname')
            ->desc('lastname', false);
        self::assertEquals("ORDER BY username ASC, firstname ASC, lastname DESC NULLS LAST", $order->getSql());
    }
}
