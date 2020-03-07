<?php namespace Zephyrus\Utilities\Formatters;

use Zephyrus\Application\Configuration;

trait NumericFormatter
{
    public static function percent($number, $minDecimals = 2, $maxDecimals = 4)
    {
        $locale = Configuration::getApplicationConfiguration('locale');
        $formatter = new \NumberFormatter($locale, \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        return $result;
    }

    public static function money($amount, $minDecimals = 2, $maxDecimals = 2)
    {
        $locale = Configuration::getApplicationConfiguration('locale');
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->formatCurrency($amount, Configuration::getApplicationConfiguration('currency'));
        return $result;
    }

    public static function decimal($number, $minDecimals = 2, $maxDecimals = 4)
    {
        $locale = Configuration::getApplicationConfiguration('locale');
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        return $result;
    }
}
