<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Formatter;

class FormatterTest extends TestCase
{
    public function testFormatDecimal()
    {
        $result = Formatter::formatDecimal(12.342743);
        self::assertEquals("12,3427", $result);
        $result = Formatter::formatDecimal(12.346);
        self::assertEquals("12,346", $result);
        $result = Formatter::formatDecimal(12.1);
        self::assertEquals("12,10", $result);
        $result = Formatter::formatDecimal(12);
        self::assertEquals("12,00", $result);
        $result = Formatter::formatDecimal("er");
        self::assertEquals("0,00", $result);
        $result = Formatter::formatDecimal(12.342743, 2, 2);
        self::assertEquals("12,34", $result);
        $result = Formatter::formatDecimal(12.346, 2, 2);
        self::assertEquals("12,35", $result);
        $result = Formatter::formatDecimal(12.13454, 0, 1);
        self::assertEquals("12,1", $result);
        $result = Formatter::formatDecimal(12.16454, 0, 1);
        self::assertEquals("12,2", $result);
        $result = Formatter::formatDecimal(12, 0, 0);
        self::assertEquals("12", $result);
    }

    public function testFormatSeoUrl()
    {
        $result = Formatter::formatSeoUrl("École en frAnçais");
        self::assertEquals("ecole-en-francais", $result);
    }

    public function testFormatMoney()
    {
        $result = Formatter::formatMoney(500.45);
        self::assertEquals('500,45 $', $result);
        $result = Formatter::formatMoney(500.459);
        self::assertEquals('500,46 $', $result);
        $result = Formatter::formatMoney(500.75657645345, 0, 0);
        self::assertEquals('501 $', $result);
    }
}