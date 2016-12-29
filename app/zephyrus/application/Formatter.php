<?php namespace Zephyrus\Application;

class Formatter
{
    public static function formatElapsedDateTime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
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

    public static function formatFrenchPeriod($startDate, $endDate)
    {
        if (!$startDate instanceof \DateTime) {
            $startDate = new \DateTime($startDate);
        }
        if (!$endDate instanceof \DateTime) {
            $endDate = new \DateTime($endDate);
        }
        $beginDateStr = $startDate->format("Y-m-d");
        $endDateStr = $endDate->format("Y-m-d");
        $beginTime = $startDate->format("H:i");
        $endTime = $endDate->format("H:i");

        if ($beginDateStr == $endDateStr) {
            return self::formatFrenchDate($startDate) . ", " . self::formatTime($startDate) . " - " . self::formatTime($endDate);
        }

        if ($beginTime == "00:00" && $endTime == "00:00") {
            return self::formatFrenchDate($startDate) . " au " . self::formatFrenchDate($endDate);
        }

        return self::formatFrenchDateTime($startDate) . " au " . self::formatFrenchDateTime($endDate);
    }

    public static function formatFrenchDate($dateTime, $capitalize = false, $useFullMonths = true)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
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

    public static function formatFrenchDateTime($dateTime, $capitalize = false, $useFullMonths = true)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return self::formatFrenchDate($dateTime, $capitalize, $useFullMonths) . ', ' . $dateTime->format('H:i');
    }

    public static function formatTime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
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
     * @param int $sizeInBytes
     * @return string
     */
    public static function formatHumanFileSize($sizeInBytes)
    {
        if ($sizeInBytes >= 1073741824) {
            $fileSize = round($sizeInBytes / 1024 / 1024 / 1024, 1) . ' go';
        } elseif ($sizeInBytes >= 1048576) {
            $fileSize = round($sizeInBytes / 1024 / 1024, 1) . ' mo';
        } elseif($sizeInBytes >= 1024) {
            $fileSize = round($sizeInBytes / 1024, 1) . ' ko';
        } else {
            $fileSize = $sizeInBytes . ' octets';
        }
        return str_replace('.', ',', $fileSize);
    }
}