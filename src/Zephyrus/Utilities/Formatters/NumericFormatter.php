<?php namespace Zephyrus\Utilities\Formatters;

use Locale;
use Zephyrus\Application\Configuration;

trait NumericFormatter
{
    public static function percent(int|float|null $number, int $minDecimals = 2, int $maxDecimals = 4): string
    {
        if (is_null($number)) {
            return "-";
        }
        $formatter = new \NumberFormatter(Locale::getDefault(), \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        return $result === false ? "-" : $result;
    }

    public static function money(int|float|null $amount, int $minDecimals = 2, int $maxDecimals = 2): string
    {
        if (is_null($amount)) {
            return "-";
        }
        $formatter = new \NumberFormatter(Locale::getDefault(), \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->formatCurrency(round($amount, $maxDecimals), Configuration::getLocaleConfiguration('currency'));
        return $result === false ? "-" : $result;
    }

    public static function decimal(int|float|null $number, int $minDecimals = 2, int $maxDecimals = 4): string
    {
        if (is_null($number)) {
            return "-";
        }
        $formatter = new \NumberFormatter(Locale::getDefault(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format(round($number, $maxDecimals), \NumberFormatter::TYPE_DOUBLE);
        return $result === false ? "-" : $result;
    }
}
