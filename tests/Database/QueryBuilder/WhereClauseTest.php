<?php namespace Zephyrus\Tests\Database\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\QueryBuilder\WhereClause;
use Zephyrus\Database\QueryBuilder\WhereCondition;

class WhereClauseTest extends TestCase
{
    public function testAnd()
    {
        $usernameCondition = WhereCondition::equals("username", "dadajuice");
        $passwordCondition = WhereCondition::equals("password", "Passw0rd!");
        $where = new WhereClause($usernameCondition);
        $where->and($passwordCondition);
        self::assertEquals("WHERE (username = ?) AND (password = ?)", $where->getSql());
        self::assertEquals(["dadajuice", "Passw0rd!"], $where->getQueryParameters());
    }

    public function testOr()
    {
        $usernameCondition = WhereCondition::equals("username", "dadajuice");
        $emailCondition = WhereCondition::equals("email", "bob@lewis.com");
        $where = new WhereClause($usernameCondition);
        $where->or($emailCondition);
        self::assertEquals("WHERE (username = ?) OR (email = ?)", $where->getSql());
        self::assertEquals(["dadajuice", "bob@lewis.com"], $where->getQueryParameters());
    }

    public function testAndMultiple()
    {
        $usernameCondition = WhereCondition::equals("username", "dadajuice");
        $passwordCondition = WhereCondition::equals("password", "Passw0rd!");
        $codeCondition = WhereCondition::equals("code", "test123");
        $where = new WhereClause($usernameCondition);
        $where->and($passwordCondition)->and($codeCondition);
        self::assertEquals("WHERE (username = ?) AND (password = ?) AND (code = ?)", $where->getSql());
        self::assertEquals(["dadajuice", "Passw0rd!", "test123"], $where->getQueryParameters());
    }

    public function testCombined()
    {
        $usernameCondition = WhereCondition::or(
            WhereCondition::equals("username", "dadajuice"),
            WhereCondition::equals("email", "bob@lewis.com"));
        $passwordCondition = WhereCondition::equals("password", "Passw0rd!");
        $where = new WhereClause($usernameCondition);
        $where->and($passwordCondition);
        self::assertEquals("WHERE ((username = ?) OR (email = ?)) AND (password = ?)", $where->getSql());
        self::assertEquals(["dadajuice", "bob@lewis.com", "Passw0rd!"], $where->getQueryParameters());
    }
}
