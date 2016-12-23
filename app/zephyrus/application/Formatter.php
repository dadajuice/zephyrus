<?php namespace Zephyrus\Application;

class Formatter
{
    public static function formatElapsedDateTime(\DateTime $dateTime)
    {
        $now = new \DateTime();
        $diff = $dateTime->diff($now);

        if ($diff->d == 0) {
            if ($diff->h == 0) {
                if ($diff->i == 0) {
                    return "Il y a " . $diff->s . " seconde" . (($diff->s > 1) ? 's' : '');
                }
                return "Il y a " . $diff->i . " minute" . (($diff->i > 1) ? 's' : '');
            }
            return "Aujourd'hui, " . self::formatTime($dateTime);
        } elseif ($diff->d == 1 && $diff->h == 0) {
            return "Hier, " . self::formatTime($dateTime);
        }

        return self::formatFrenchDateTime($dateTime);
    }

    public static function formatFrenchPeriod(\DateTime $startDate, \DateTime $endDate)
    {
        $beginDateStr = $startDate->format("Y-m-d");
        $endDateStr = $endDate->format("Y-m-d");
        $beginTime = $startDate->format("H:i");
        $endTime = $endDate->format("H:i");

        if ($beginDateStr == $endDateStr) {
            return formatFrenchDate($startDate) . ", " . formatTime($startDate) . " - " . formatTime($endDate);
        }

        if ($beginTime == "00:00" && $endTime == "00:00") {
            return formatFrenchDate($startDate) . " au " . formatFrenchDate($endDate);
        }

        return formatFrenchDateTime($startDate) . " au " . formatFrenchDateTime($endDate);
    }

    public static function formatFrenchDate(\DateTime $dateTime, $capitalize = false, $useFullMonths = true)
    {
        $partialMonths = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jui', 'aoû', 'sep', 'oct', 'nov', 'déc'];
        $fullMonths = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        $i = $dateTime->format('n') - 1;
        $month = ($useFullMonths)
            ? $fullMonths[$i]
            : $partialMonths[$i];
        if ($capitalize) {
            $month = ucfirst($month);
        }
        return $dateTime->format('j') . ' ' . $month . ' ' . $dateTime->format('Y');
    }

    public static function formatFrenchDateTime(\DateTime $dateTime, $capitalize = false, $useFullMonths = true)
    {
        return self::formatFrenchDate($dateTime, $capitalize, $useFullMonths) . ', ' . $dateTime->format('H:i');
    }

    public static function formatTime(\DateTime $dateTime)
    {
        return $dateTime->format('H:i');
    }

    public static function formatPercent($number, $minDecimals = 2, $maxDecimals = 4)
    {
        $formatter = new \NumberFormatter('fr_CA', \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::DECIMAL_ALWAYS_SHOWN, ($minDecimals > 0 || $maxDecimals > 0));
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        if (intl_is_failure($formatter->getErrorCode())) {
            throw new \Exception("Formatter error : " . $formatter->getErrorMessage());
        }
        return $result;
    }

    public static function formatMoney($amount, $minDecimals = 2, $maxDecimals = 2, $roundUp = true)
    {
        $negative = $amount < 0;
        $decimal = self::formatDecimal(abs($amount), $minDecimals, $maxDecimals, $roundUp);
        if ($negative) {
            $decimal = "($decimal)";
        }
        return $decimal . ' $';
    }

    public static function formatDecimal($number, $minDecimals = 2, $maxDecimals = 4, $roundUp = true)
    {
        $formatter = new \NumberFormatter('fr_CA', \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::DECIMAL_ALWAYS_SHOWN, ($minDecimals > 0 || $maxDecimals > 0));
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        if ($roundUp && $minDecimals == 0 && $maxDecimals == 0) {
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_UP);
        }
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        if (intl_is_failure($formatter->getErrorCode())) {
            throw new \Exception("Formatter error : " . $formatter->getErrorMessage());
        }
        return $result;
    }

    /**
     * http://stackoverflow.com/questions/15188033/human-readable-file-size
     * @param int $size in bytes
     * @return string
     */
    public static function formatHumanFileSize($size)
    {
        if ($size >= 1073741824) {
            $fileSize = round($size / 1024 / 1024 / 1024, 1) . ' go';
        } elseif ($size >= 1048576) {
            $fileSize = round($size / 1024 / 1024, 1) . ' mo';
        } elseif($size >= 1024) {
            $fileSize = round($size / 1024, 1) . ' ko';
        } else {
            $fileSize = $size . ' octets';
        }
        return str_replace('.', ',', $fileSize);
    }
}