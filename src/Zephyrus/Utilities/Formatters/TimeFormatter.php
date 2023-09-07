<?php namespace Zephyrus\Utilities\Formatters;

use DateTime;
use IntlDateFormatter;
use Locale;
use Zephyrus\Application\Configuration;

trait TimeFormatter
{
    public static function date(DateTime|string|null $dateTime): string
    {
        if (is_null($dateTime)) {
            return "-";
        }
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::NONE, null, null, Configuration::getLocale('format_date', "d LLLL yyyy"));
        return $formatter->format($dateTime->getTimestamp()) ?: "-";
    }

    public static function datetime(DateTime|string|null $dateTime): string
    {
        if (is_null($dateTime)) {
            return "-";
        }
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::LONG, IntlDateFormatter::SHORT, null, null, Configuration::getLocale('format_datetime', "d LLLL yyyy, HH:mm"));
        return $formatter->format($dateTime->getTimestamp()) ?: "-";
    }

    public static function time(DateTime|string|null $dateTime): string
    {
        if (is_null($dateTime)) {
            return "-";
        }
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        $formatter = new IntlDateFormatter(Locale::getDefault(), IntlDateFormatter::NONE, IntlDateFormatter::LONG, null, null, Configuration::getLocale('format_time', "HH:mm"));
        return $formatter->format($dateTime->getTimestamp()) ?: "-";
    }

    public static function duration($seconds, $minuteSuffix = ":", $hourSuffix = ":", $secondSuffix = "")
    {
        return gmdate("H" . $hourSuffix . "i" . $minuteSuffix . "s" . $secondSuffix, $seconds);
    }
}
