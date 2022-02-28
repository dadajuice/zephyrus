<?php namespace Zephyrus\Utilities\Formatters;

use DateTime;
use IntlDateFormatter;
use Locale;
use Zephyrus\Application\Configuration;

trait TimeFormatter
{
    public static function date($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, Configuration::getConfiguration('lang', 'date', "d LLLL yyyy"));
        return $formatter->format($dateTime->getTimestamp());
    }

    public static function datetime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::SHORT, null, null, Configuration::getConfiguration('lang', 'datetime', " d LLLL yyyy, H:mm"));
        return $formatter->format($dateTime->getTimestamp());
    }

    public static function time($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::NONE, IntlDateFormatter::LONG, null, null, Configuration::getConfiguration('lang', 'time', "H:mm"));
        return $formatter->format($dateTime->getTimestamp());
    }

    public static function duration($seconds, $minuteSuffix = ":", $hourSuffix = ":", $secondSuffix = "")
    {
        return gmdate("H" . $hourSuffix . "i" . $minuteSuffix . "s" . $secondSuffix, $seconds);
    }
}
