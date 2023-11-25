<?php namespace Zephyrus\Tests\Database\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\QueryBuilder\LimitClause;

class LimitClauseTest extends TestCase
{
    public function testLimit()
    {
        $limit = new LimitClause();
        $limit->setLimit(50);
        self::assertEquals("LIMIT 50", $limit->getSql());
    }

    public function testLimitWithOffset()
    {
        $limit = new LimitClause();
        $limit->setLimit(50);
        $limit->setOffset(10);
        self::assertEquals("LIMIT 50 OFFSET 10", $limit->getSql());
    }
}
