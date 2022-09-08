<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Formatter;
use Locale;

class FormatterTest extends TestCase
{
    public static function setUpBeforeClass(): void {
        //Since locale test are written for "fr_CA" format, we have to enforce the locale here
        Locale::setDefault('fr_CA');
    }

    public function testFormatDecimal()
    {
        $result = Formatter::decimal(12.342743);
        self::assertEquals("12,3427", $result);
        $result = Formatter::decimal(12.346);
        self::assertEquals("12,346", $result);
        $result = Formatter::decimal(12.1);
        self::assertEquals("12,10", $result);
        $result = Formatter::decimal(12);
        self::assertEquals("12,00", $result);
        $result = Formatter::decimal(12.342743, 2, 2);
        self::assertEquals("12,34", $result);
        $result = Formatter::decimal(12.346, 2, 2);
        self::assertEquals("12,35", $result);
        $result = Formatter::decimal(12.13454, 0, 1);
        self::assertEquals("12,1", $result);
        $result = Formatter::decimal(12.16454, 0, 1);
        self::assertEquals("12,2", $result);
        $result = Formatter::decimal(12, 0, 0);
        self::assertEquals("12", $result);
        $result = Formatter::decimal(0, 0, 0);
        self::assertEquals("0", $result);
        $result = Formatter::decimal(null, 0, 0);
        self::assertEquals("-", $result);
        $result = Formatter::decimal(27.5*11.29, 0, 2); //Test of floating point errors
        self::assertEquals("310,48", $result);
    }

    public function testEllipsis()
    {
        $result = Formatter::ellipsis("Lorem ipsum", 4);
        self::assertEquals("Lore...", $result);
        $result = Formatter::ellipsis("6465784", 4);
        self::assertEquals("6465...", $result);
    }

    public function testFormatMoney()
    {
        $result = Formatter::money(500.45);
        self::assertEquals('500,45 $', $result);
        $result = Formatter::money(500.459);
        self::assertEquals('500,46 $', $result);
        $result = Formatter::money(500.75657645345, 0, 0);
        self::assertEquals('501 $', $result);
        $result = Formatter::money(500.455);
        self::assertEquals('500,46 $', $result);
        $result = Formatter::money(27.5*11.29);
        self::assertEquals('310,48 $', $result); //Breaks because multiplication is float(310.47499999999997) so is rounded down...
        $result = Formatter::money(310.475);
        self::assertEquals('310,48 $', $result);
        $result = Formatter::money(2.265);
        self::assertEquals('2,27 $', $result);
        $result = Formatter::money(2.275);
        self::assertEquals('2,28 $', $result);
        $result = Formatter::money(2.2650);
        self::assertEquals('2,27 $', $result);
        $result = Formatter::money(2.263);
        self::assertEquals('2,26 $', $result);
        $result = Formatter::money(0);
        self::assertEquals('0,00 $', $result);
        $result = Formatter::money(null);
        self::assertEquals('-', $result);
    }

    public function testFormatPercent()
    {
        $result = Formatter::percent(0.15);
        self::assertEquals('15,00 %', $result);
        $result = Formatter::percent(0.15, 0, 0);
        self::assertEquals('15 %', $result);
        $result = Formatter::percent(0.875, 1);
        self::assertEquals('87,5 %', $result);
        $result = Formatter::percent(null, 1);
        self::assertEquals('-', $result);
        $result = Formatter::percent(2.05*1.28, 1, 2);
        self::assertEquals('262,4 %', $result);
    }

    public function testFormatTime()
    {
        $result = Formatter::time('2016-01-01 23:15:00');
        self::assertEquals('23:15', $result);

        $result = Formatter::time(null);
        self::assertEquals('-', $result);
    }

    public function testFormatFrenchDate()
    {
        $result = Formatter::date('2016-01-01 23:15:00');
        self::assertEquals('1 janvier 2016', $result);

        $result = Formatter::date(null);
        self::assertEquals('-', $result);
    }

    public function testFormatFrenchDateTime()
    {
        $result = Formatter::datetime('2016-01-01 23:15:00');
        self::assertEquals('1 janvier 2016, 23:15', $result);

        $result = Formatter::datetime('2016-01-01 01:15:00');
        self::assertEquals('1 janvier 2016, 01:15', $result);

        $result = Formatter::datetime(null);
        self::assertEquals('-', $result);
    }

    public function testFormatSizeKb()
    {
        $result = Formatter::filesize(1000000);
        self::assertEquals('976,6 kb', $result);
    }

    public function testFormatSizeMb()
    {
        $result = Formatter::filesize(2000000);
        self::assertEquals('1,9 mb', $result);
    }

    public function testFormatSizeGb()
    {
        $result = Formatter::filesize(2000000000);
        self::assertEquals('1,9 gb', $result);
    }

    public function testFormatSizeNull()
    {
        $result = Formatter::filesize(null);
        self::assertEquals('-', $result);
    }

    public function testDuration()
    {
        $result = Formatter::duration(30000);
        self::assertEquals('08:20:00', $result);
    }

    public function testRegisterCustomFormat()
    {
        Formatter::register('volume', function ($value) {
            return Formatter::decimal($value, 2, 2) . " m<sup>3</sup>";
        });
        $result = format('volume', 120.1);
        self::assertEquals('120,10 m<sup>3</sup>', $result);
        $result = Formatter::volume(120.1);
        self::assertEquals('120,10 m<sup>3</sup>', $result);
    }

    public function testInvalidCustomFormat()
    {
        $this->expectException(\BadMethodCallException::class);
        format('temperature', -5);
    }

//    private function enableFrenchSettings()
//    {
//        date_default_timezone_set('America/Montreal');
//        setlocale(LC_MESSAGES, 'fr_CA.utf8');
//        setlocale(LC_TIME, 'fr_CA.utf8');
//        setlocale(LC_CTYPE, 'fr_CA.utf8');
//        putenv("LANG=fr_CA.utf8");
//    }
}