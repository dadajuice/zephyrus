<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validator;

class RuleTest extends TestCase
{
    public function testIsValid()
    {
        $rule = new Rule(function($value) {
            return $value == 'allo';
        }, "failed");
        self::assertTrue($rule->isValid('allo'));
        self::assertEquals('failed', $rule->getErrorMessage());
    }

    public function testIsValidOptionalMessage()
    {
        $rule = new Rule(Validator::EMAIL);
        $rule->setErrorMessage("failed");
        self::assertTrue($rule->isValid('allo@bob.com'));
        self::assertEquals('failed', $rule->getErrorMessage());
    }

    public function testIsPasswordCompliant()
    {
        $rule = Rule::passwordCompliant("err");
        self::assertTrue($rule->isValid('Omega1234'));
        self::assertFalse($rule->isValid('bob'));
    }

    public function testIsDecimal()
    {
        $rule = Rule::decimal("err");
        self::assertTrue($rule->isValid(1.2));
        self::assertFalse($rule->isValid(-2.5));
        $rule = Rule::decimal("err", true);
        self::assertTrue($rule->isValid(-1.2));
    }

    public function testIsInteger()
    {
        $rule = Rule::integer("err");
        self::assertTrue($rule->isValid(1));
        self::assertFalse($rule->isValid(-2));
        $rule = Rule::integer("err", true);
        self::assertTrue($rule->isValid(-1));
    }

    public function testIsEmail()
    {
        $rule = Rule::email("err");
        self::assertTrue($rule->isValid("bob@lewis.com"));
        self::assertFalse($rule->isValid("bob"));
    }

    public function testIsDate()
    {
        $rule = Rule::date("err");
        self::assertTrue($rule->isValid("2017-01-01"));
        self::assertFalse($rule->isValid("2017-50-03"));
    }

    public function testIsTime12Hours()
    {
        $rule = Rule::time12Hours("err");
        self::assertTrue($rule->isValid("08:07"));
        self::assertTrue($rule->isValid("00:00"));
        self::assertFalse($rule->isValid("23:45"));
    }

    public function testIsTime24Hours()
    {
        $rule = Rule::time24Hours("err");
        self::assertTrue($rule->isValid("23:07"));
        self::assertTrue($rule->isValid("00:00"));
        self::assertFalse($rule->isValid("34:45"));
    }

    public function testIsPhone()
    {
        $rule = Rule::phone("err");
        self::assertTrue($rule->isValid("450-666-6666"));
        self::assertFalse($rule->isValid("boby"));
    }

    public function testIsAlpha()
    {
        $rule = Rule::alpha("err");
        self::assertTrue($rule->isValid("bob"));
        self::assertFalse($rule->isValid("450-666-6666"));
    }

    public function testIsName()
    {
        $rule = Rule::name("err");
        self::assertTrue($rule->isValid("Émilie Bornard"));
        self::assertFalse($rule->isValid("450-666-6666"));
    }

    public function testIsAlphanumeric()
    {
        $rule = Rule::alphanumeric("err");
        self::assertTrue($rule->isValid("bob34"));
        self::assertFalse($rule->isValid("dslfj**"));
    }

    public function testIsUrl()
    {
        $rule = Rule::url("err");
        self::assertTrue($rule->isValid("http://www.google.ca"));
        self::assertFalse($rule->isValid("allo.com"));
    }
}
