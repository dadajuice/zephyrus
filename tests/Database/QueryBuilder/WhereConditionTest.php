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

    public function testAnd()
    {
        $condition1 = WhereCondition::equals("username", "admin");
        $condition2 = WhereCondition::equals("password", "Passw0rd123!");
        $condition = WhereCondition::and($condition1, $condition2);
        self::assertEquals("((username = ?) AND (password = ?))", $condition->getSql());
        self::assertEquals(["admin", "Passw0rd123!"], $condition->getQueryParameters());
    }

    public function testOr()
    {
        $condition1 = WhereCondition::equals("username", "admin");
        $condition2 = WhereCondition::equals("username", "system");
        $condition3 = WhereCondition::equals("username", "root");
        $condition = WhereCondition::or($condition1, $condition2, $condition3);
        self::assertEquals("((username = ?) OR (username = ?) OR (username = ?))", $condition->getSql());
        self::assertEquals(["admin", "system", "root"], $condition->getQueryParameters());
    }

    public function testNot()
    {
        $condition1 = WhereCondition::equals("username", "admin");
        $condition = WhereCondition::not($condition1);
        self::assertEquals("(NOT (username = ?))", $condition->getSql());
        self::assertEquals(["admin"], $condition->getQueryParameters());
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

    public function testSimilarTo()
    {
        $condition = WhereCondition::similarTo("firstname", '%(b|d)%');
        self::assertEquals("(firstname SIMILAR TO ?)", $condition->getSql());
        self::assertEquals(["%(b|d)%"], $condition->getQueryParameters());
    }

    public function testLess()
    {
        $condition = WhereCondition::less("price", 120);
        self::assertEquals("(price < ?)", $condition->getSql());
        self::assertEquals([120], $condition->getQueryParameters());
    }

    public function testGreater()
    {
        $condition = WhereCondition::greater("price", 120);
        self::assertEquals("(price > ?)", $condition->getSql());
        self::assertEquals([120], $condition->getQueryParameters());
    }

    public function testLessEquals()
    {
        $condition = WhereCondition::lessEquals("price", 120);
        self::assertEquals("(price <= ?)", $condition->getSql());
        self::assertEquals([120], $condition->getQueryParameters());
    }

    public function testGreaterEquals()
    {
        $condition = WhereCondition::greaterEquals("price", 120);
        self::assertEquals("(price >= ?)", $condition->getSql());
        self::assertEquals([120], $condition->getQueryParameters());
    }

    public function testInArray()
    {
        $condition = WhereCondition::inArray("type", [12, 65, 90]);
        self::assertEquals("(type IN(?, ?, ?))", $condition->getSql());
        self::assertEquals([12, 65, 90], $condition->getQueryParameters());
    }

    public function testSubQuery()
    {
        $condition = WhereCondition::inSubQuery("project_id", "SELECT project_id FROM project WHERE id = ?", [12]);
        self::assertEquals("(project_id IN(SELECT project_id FROM project WHERE id = ?))", $condition->getSql());
        self::assertEquals([12], $condition->getQueryParameters());
    }

    public function testExists()
    {
        $condition = WhereCondition::exists("project_id", "SELECT project_id FROM project WHERE id = ?", [12]);
        self::assertEquals("(project_id EXISTS(SELECT project_id FROM project WHERE id = ?))", $condition->getSql());
        self::assertEquals([12], $condition->getQueryParameters());
    }

    public function testNotExists()
    {
        $condition = WhereCondition::notExists("project_id", "SELECT project_id FROM project WHERE id = ?", [12]);
        self::assertEquals("(project_id NOT EXISTS(SELECT project_id FROM project WHERE id = ?))", $condition->getSql());
        self::assertEquals([12], $condition->getQueryParameters());
    }

    public function testBetween()
    {
        $condition = WhereCondition::between("date", "2022-01-01", "2022-03-01");
        self::assertEquals("(date BETWEEN ? AND ?)", $condition->getSql());
        self::assertEquals(["2022-01-01", "2022-03-01"], $condition->getQueryParameters());
    }
}
