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
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, "d LLLL yyyy");
        return $formatter->format($dateTime->getTimestamp());
        //return strftime(Configuration::getConfiguration('lang', 'date'), $dateTime->getTimestamp());
    }

    public static function datetime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        //1 janvier 2016, 23:15
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::SHORT, null, null, " d LLLL yyyy, H:mm");
        return $formatter->format($dateTime->getTimestamp());

        //return strftime(Configuration::getConfiguration('lang', 'datetime'), $dateTime->getTimestamp());
    }

    public static function time($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::NONE, IntlDateFormatter::LONG, null, null, "H:mm");
        return $formatter->format($dateTime->getTimestamp());

        //return strftime(Configuration::getConfiguration('lang', 'time'), $dateTime->getTimestamp());
    }

    public static function duration($seconds, $minuteSuffix = ":", $hourSuffix = ":", $secondSuffix = "")
    {
        return gmdate("H" . $hourSuffix . "i" . $minuteSuffix . "s" . $secondSuffix, $seconds);
    }
}
