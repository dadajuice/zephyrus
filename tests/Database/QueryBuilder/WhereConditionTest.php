<?php namespace Zephyrus\Tests\Database\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\QueryBuilder\WhereCondition;

class WhereConditionTest extends TestCase
{
    public function testEquals()
    {
        $condition = WhereCondition::equals("username", "bob");
        self::assertEquals("(username = ?)", $condition->getSql());
        self::assertEquals(["bob"], $condition->getQueryParameters());
    }

    public function testNotEquals()
    {
        $condition = WhereCondition::notEquals("username", "bob");
        self::assertEquals("(username != ?)", $condition->getSql());
        self::assertEquals(["bob"], $condition->getQueryParameters());
    }

    public function testIsNull()
    {
        $condition = WhereCondition::isNull("email");
        self::assertEquals("(email IS NULL)", $condition->getSql());
        self::assertEmpty($condition->getQueryParameters());
    }

    public function testIsNotNull()
    {
        $condition = WhereCondition::isNotNull("email");
        self::assertEquals("(email IS NOT NULL)", $condition->getSql());
        self::assertEmpty($condition->getQueryParameters());
    }

    public function testLike()
    {
        $condition = WhereCondition::like("firstname", "%roland%");
        self::assertEquals("(firstname ILIKE ?)", $condition->getSql());
        self::assertEquals(["%roland%"], $condition->getQueryParameters());
    }

    public function testBetween()
    {
        $condition = WhereCondition::between("date", "2022-01-01", "2022-03-01");
        self::assertEquals("(date BETWEEN ? AND ?)", $condition->getSql());
        self::assertEquals(["2022-01-01", "2022-03-01"], $condition->getQueryParameters());
    }
}
