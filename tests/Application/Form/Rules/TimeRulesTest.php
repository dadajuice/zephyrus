<?php namespace Zephyrus\Tests\Application\Form\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class TimeRulesTest extends TestCase
{
    public function testIsDate()
    {
        $rule = Rule::date();
        self::assertTrue($rule->isValid("2017-01-01"));
        self::assertTrue($rule->isValid("2016-01-01"));
        self::assertFalse($rule->isValid("-109-01-01"));
        self::assertFalse($rule->isValid("2016-31-31"));
        self::assertFalse($rule->isValid("2016-02-30"));
        self::assertFalse($rule->isValid("-109-01-01"));
        self::assertFalse($rule->isValid("2016"));
        self::assertFalse($rule->isValid("2017-50-03"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsDateBefore()
    {
        $rule = Rule::dateBefore('2019-01-12');
        self::assertTrue($rule->isValid("2017-01-01"));
        self::assertTrue($rule->isValid("2019-01-11"));
        self::assertFalse($rule->isValid("2019-01-12"));
        self::assertFalse($rule->isValid("2020-01-12"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsDateAfter()
    {
        $rule = Rule::dateAfter('2019-01-12');
        self::assertFalse($rule->isValid("2017-01-01"));
        self::assertFalse($rule->isValid("2019-01-11"));
        self::assertFalse($rule->isValid("2019-01-12"));
        self::assertTrue($rule->isValid("2019-01-13"));
        self::assertTrue($rule->isValid("2020-01-12"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsDateBetween()
    {
        $rule = Rule::dateBetween('2018-01-12', '2019-01-12');
        self::assertFalse($rule->isValid("2017-01-01"));
        self::assertFalse($rule->isValid("2019-01-15"));
        self::assertFalse($rule->isValid("2019-01-12"));
        self::assertFalse($rule->isValid("2018-01-12"));
        self::assertTrue($rule->isValid("2018-05-13"));
        self::assertTrue($rule->isValid("2018-08-23"));
        self::assertTrue($rule->isValid("2019-01-11"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsTime12Hours()
    {
        $rule = Rule::time12Hours();
        self::assertTrue($rule->isValid("08:07"));
        self::assertTrue($rule->isValid("00:00"));
        self::assertTrue($rule->isValid("01:56"));
        self::assertFalse($rule->isValid("23:45"));
        self::assertFalse($rule->isValid("-08:00"));
        self::assertFalse($rule->isValid("er"));
        self::assertFalse($rule->isValid("05:89"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsTime24Hours()
    {
        $rule = Rule::time24Hours();
        self::assertTrue($rule->isValid("23:07"));
        self::assertTrue($rule->isValid("22:56"));
        self::assertTrue($rule->isValid("08:12"));
        self::assertTrue($rule->isValid("00:00"));
        self::assertFalse($rule->isValid("34:45"));
        self::assertFalse($rule->isValid("26:45"));
        self::assertFalse($rule->isValid("-08:00"));
        self::assertFalse($rule->isValid("er"));
        self::assertFalse($rule->isValid("05:89"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsDateTime12Hours()
    {
        $rule = Rule::dateTime12Hours();
        self::assertTrue($rule->isValid("2019-01-01 11:07"));
        self::assertFalse($rule->isValid("2019-01-01 23:07"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsDateTime24Hours()
    {
        $rule = Rule::dateTime24Hours();
        self::assertTrue($rule->isValid("2019-01-01 11:07"));
        self::assertTrue($rule->isValid("2019-01-01 23:07"));
        self::assertFalse($rule->isValid("2019-01-01 28:27"));
        self::assertFalse($rule->isValid(""));
    }

    public function testIsDateTime24HoursWithSeconds()
    {
        $rule = Rule::dateTime24Hours("", true);
        self::assertTrue($rule->isValid("2019-01-01 11:07:56"));
        self::assertTrue($rule->isValid("2019-01-01 23:07:01"));
        self::assertFalse($rule->isValid("2019-01-01 20:27:99"));
        self::assertFalse($rule->isValid("2019-01-01 20:27"));
        self::assertFalse($rule->isValid(""));
    }
}