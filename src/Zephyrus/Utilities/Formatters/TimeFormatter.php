<?php namespace Zephyrus\Utilities\Formatters;

use DateTime;
use Zephyrus\Application\Configuration;

trait TimeFormatter
{
    public static function date($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'date'), $dateTime->getTimestamp());
    }

    public static function datetime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'datetime'), $dateTime->getTimestamp());
    }

    public static function time($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'time'), $dateTime->getTimestamp());
    }

    public static function duration($seconds, $minuteSuffix = ":", $hourSuffix = ":", $secondSuffix = "")
    {
        return gmdate("H" . $hourSuffix . "i" . $minuteSuffix . "s" . $secondSuffix, $seconds);
    }
}
