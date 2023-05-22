<?php namespace Zephyrus\Tests\Application\Form\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class BaseRulesTest extends TestCase
{
    public function testIsDecimal()
    {
        $rule = Rule::decimal();
        self::assertEquals("decimal", $rule->getName());
        self::assertTrue($rule->isValid(1.2));
        self::assertFalse($rule->isValid(-2.5));
        self::assertTrue($rule->isValid('10.45657'));
        self::assertTrue($rule->isValid('10,45657'));
        self::assertTrue($rule->isValid('10'));
        self::assertFalse($rule->isValid('ert'));
        self::assertFalse($rule->isValid('0ert'));
        self::assertFalse($rule->isValid('ert0'));
        self::assertFalse($rule->isValid('.'));
        self::assertFalse($rule->isValid('0.'));
        self::assertFalse($rule->isValid('.0'));
        self::assertFalse($rule->isValid('-1'));
    }

    public function testIsInteger()
    {
        $rule = Rule::integer();
        self::assertEquals("integer", $rule->getName());
        self::assertTrue($rule->isValid('10'));
        self::assertFalse($rule->isValid('10.34'));
        self::assertFalse($rule->isValid('-10'));
        self::assertFalse($rule->isValid('er'));
    }

    public function testIsSignedDecimal()
    {
        $rule = Rule::decimal("err", true);
        self::assertTrue($rule->isValid('10.45657'));
        self::assertTrue($rule->isValid('10,45657'));
        self::assertTrue($rule->isValid('10'));
        self::assertFalse($rule->isValid('ert'));
        self::assertFalse($rule->isValid('0ert'));
        self::assertFalse($rule->isValid('ert0'));
        self::assertFalse($rule->isValid('.'));
        self::assertFalse($rule->isValid('0.'));
        self::assertFalse($rule->isValid('.0'));
        self::assertTrue($rule->isValid('-1'));
        self::assertTrue($rule->isValid('-1.0'));
        self::assertTrue($rule->isValid('-1,34'));
        self::assertTrue($rule->isValid('-1234,12345'));
    }

    public function testIsSignedInteger()
    {
        $rule = Rule::integer("", true);
        self::assertTrue($rule->isValid('10'));
        self::assertTrue($rule->isValid('-10'));
        self::assertFalse($rule->isValid('-10,34'));
        self::assertFalse($rule->isValid('er'));
    }

    public function testIsInRange()
    {
        $rule = Rule::range(0, 6);
        self::assertTrue($rule->isValid(4));
        self::assertFalse($rule->isValid(-5));
        self::assertFalse($rule->isValid(7));
        self::assertTrue($rule->isValid(6));
    }

    public function testIsSameAs()
    {
        $rule = Rule::sameAs("password", "err");
        self::assertTrue($rule->isValid("1234", ["password" => "1234", "username" => "blewis"]));
        self::assertFalse($rule->isValid("1234", ["password" => "5678", "username" => "blewis"]));
        self::assertFalse($rule->isValid("1234", ["username" => "blewis"]));
    }

    public function testIsBoolean()
    {
        $rule = Rule::boolean("err");
        self::assertTrue($rule->isValid("true"));
        self::assertTrue($rule->isValid("false"));
        self::assertTrue($rule->isValid(false));
        self::assertTrue($rule->isValid(true));
        self::assertTrue($rule->isValid(0));
        self::assertTrue($rule->isValid(1));
        self::assertFalse($rule->isValid("hello"));
        self::assertFalse($rule->isValid("e"));
        self::assertFalse($rule->isValid(56));
    }

    public function testIsArray()
    {
        $rule = Rule::array("err");
        self::assertEquals("array", $rule->getName());
        self::assertTrue($rule->isValid(["1", 2, "hello"]));
        self::assertFalse($rule->isValid("e"));
        self::assertTrue($rule->isValid(["bat" => "man"]));
    }

    public function testLength()
    {
        $rule = Rule::length(3, "err");
        self::assertEquals("length", $rule->getName());
        self::assertTrue($rule->isValid(["1", 2, "hello"]));
        self::assertFalse($rule->isValid("e"));
        self::assertFalse($rule->isValid(["bat" => "man"]));
        self::assertFalse($rule->isValid([1, 2, 3, 4]));
        self::assertTrue($rule->isValid("hel"));
        self::assertFalse($rule->isValid("hello"));
        self::assertFalse($rule->isValid("he"));
        self::assertFalse($rule->isValid(123));
        self::assertFalse($rule->isValid(true));
    }

    public function testSameLengthAs()
    {
        $rule = Rule::sameLengthAs('test', "err");
        self::assertEquals("sameLengthAs", $rule->getName());
        self::assertTrue($rule->isValid(["1", 2, "hello"], ['test' => [1, 2, 3]]));
        self::assertFalse($rule->isValid("e", ['test' => [1, 2, 3]]));
        self::assertFalse($rule->isValid(["bat" => "man"], ['test' => [1, 2, 3]]));
        self::assertFalse($rule->isValid([1, 2, 3, 4], ['test' => [1, 2, 3]]));
        self::assertTrue($rule->isValid("bat", ['test' => 'man']));
        self::assertFalse($rule->isValid("bat", ['test' => 'mano']));
    }

    public function testIsObject()
    {
        $rule = Rule::object("err");
        self::assertTrue($rule->isValid((object) ["name" => 'Bob', "age" => 18]));
        self::assertFalse($rule->isValid("e"));
        self::assertFalse($rule->isValid(["bat" => "man"]));
    }

    public function testWithAnonymous()
    {
        $rule = new Rule(function ($value) {
            return $value == "42";
        }, "err", "test");
        self::assertEquals("test", $rule->getName());
        self::assertTrue($rule->isValid(42));
        self::assertFalse($rule->isValid("e"));
    }
}