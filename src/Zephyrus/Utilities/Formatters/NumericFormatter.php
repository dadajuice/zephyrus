<?php namespace Zephyrus\Utilities\Formatters;

use Zephyrus\Application\Configuration;

trait NumericFormatter
{
    public static function percent(int|float|null $number, int $minDecimals = 2, int $maxDecimals = 4): string
    {
        if (is_null($number)) {
            return "-";
        }

        $locale = Configuration::getApplicationConfiguration('locale');
        $formatter = new \NumberFormatter($locale, \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE) ?: "-";
        return $result;
    }

    public static function money(int|float|null $amount, int $minDecimals = 2, int $maxDecimals = 2): string
    {
        if (is_null($amount)) {
            return "-";
        }

        $locale = Configuration::getApplicationConfiguration('locale');
        $formatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->formatCurrency($amount, Configuration::getApplicationConfiguration('currency')) ?: "-";
        return $result;
    }

    public static function decimal(int|float|null $number, int $minDecimals = 2, int $maxDecimals = 4): string
    {
        if (is_null($number)) {
            return "-";
        }

        $locale = Configuration::getApplicationConfiguration('locale');
        $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_HALFUP);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE) ?: "-";
        return $result;
    }
}
