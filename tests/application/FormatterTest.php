<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Formatter;

class FormatterTest extends TestCase
{
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        date_default_timezone_set('America/New_York');
        setlocale(LC_MESSAGES, 'fr_CA.utf8');
        setlocale(LC_TIME, 'fr_CA.utf8');
        setlocale(LC_CTYPE, 'fr_CA.utf8');
    }

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

    public function testFormatPercent()
    {
        $result = Formatter::formatPercent(0.15);
        self::assertEquals('15,00 %', $result);
        $result = Formatter::formatPercent(0.15, 0, 0);
        self::assertEquals('15 %', $result);
        $result = Formatter::formatPercent(0.875, 1);
        self::assertEquals('87,5 %', $result);
    }

    public function testFormatTime()
    {
        $result = Formatter::formatTime('2016-01-01 23:15:00');
        self::assertEquals('23:15', $result);
    }

    public function testFormatDate()
    {
        $result = Formatter::formatDate('2016-01-01 23:15:00');
        self::assertEquals(' 1 janvier 2016', $result);
    }

    public function testFormatDateTime()
    {
        $result = Formatter::formatDateTime('2016-01-01 23:15:00');
        self::assertEquals(' 1 janvier 2016, 23:15', $result);
    }

    public function testFormatElapsed()
    {
        $result = Formatter::formatElapsedDateTime('2016-01-01 23:15:00');
        self::assertEquals(' 1 janvier 2016, 23:15', $result);
    }

    public function testFormatElapsedSeconds()
    {
        $result = Formatter::formatElapsedDateTime('2016-01-01 23:15:10', '2016-01-01 23:15:20');
        self::assertEquals('Il y a 10 secondes', $result);
    }

    public function testFormatElapsedMinutes()
    {
        $result = Formatter::formatElapsedDateTime('2016-01-01 23:16:10', '2016-01-01 23:14:20');
        self::assertEquals('Il y a 1 minute', $result);
    }

    public function testFormatElapsedYesterday()
    {
        $result = Formatter::formatElapsedDateTime('2015-12-31 23:00:10', '2016-01-01 23:14:20');
        self::assertEquals('Hier 23:00', $result);
    }

    public function testFormatElapsedToday()
    {
        $result = Formatter::formatElapsedDateTime('2016-01-01 10:00:10', '2016-01-01 23:14:20');
        self::assertEquals('Aujourd\'hui 10:00', $result);
    }

    public function testFormatSizeKb()
    {
        $result = Formatter::formatHumanFileSize(1000000);
        self::assertEquals('976,6 ko', $result);
    }

    public function testFormatSizeMb()
    {
        $result = Formatter::formatHumanFileSize(2000000);
        self::assertEquals('1,9 mo', $result);
    }

    public function testFormatSizeGb()
    {
        $result = Formatter::formatHumanFileSize(2000000000);
        self::assertEquals('1,9 go', $result);
    }
}