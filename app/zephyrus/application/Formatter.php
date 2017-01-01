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

        return self::formatDateTime($dateTime);
    }

    public static function formatDate($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'date'), $dateTime->getTimestamp());
    }

    public static function formatDateTime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'datetime'), $dateTime->getTimestamp());
    }

    public static function formatTime($dateTime)
    {
        if (!$dateTime instanceof \DateTime) {
            $dateTime = new \DateTime($dateTime);
        }
        return strftime(Configuration::getConfiguration('lang', 'time'), $dateTime->getTimestamp());
    }

    public static function formatPercent($number, $minDecimals = 2, $maxDecimals = 4)
    {
        $formatter = new \NumberFormatter(Configuration::getApplicationConfiguration('locale'), \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        $result = $formatter->format($number, \NumberFormatter::TYPE_DOUBLE);
        if (intl_is_failure($formatter->getErrorCode())) {
            throw new \Exception("Formatter error : " . $formatter->getErrorMessage());
        }
        return $result;
    }

    public static function formatMoney($amount, $minDecimals = 2, $maxDecimals = 2, $roundUp = false)
    {
        $formatter = new \NumberFormatter(Configuration::getApplicationConfiguration('locale'), \NumberFormatter::CURRENCY);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $maxDecimals);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $minDecimals);
        if ($roundUp && $minDecimals == 0 && $maxDecimals == 0) {
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, \NumberFormatter::ROUND_UP);
        }
        $result = $formatter->formatCurrency($amount, Configuration::getApplicationConfiguration('currency'));
        if (intl_is_failure($formatter->getErrorCode())) {
            throw new \Exception("Formatter error : " . $formatter->getErrorMessage());
        }
        return $result;
    }

    public static function formatDecimal($number, $minDecimals = 2, $maxDecimals = 4, $roundUp = false)
    {
        $formatter = new \NumberFormatter(Configuration::getApplicationConfiguration('locale'), \NumberFormatter::DECIMAL);
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
     * Returns a SEO compatible url based on the specified string. Be sure to check the LC_CTYPE locale setting if
     * getting any question marks in result. Run locale -a on server to see full list of supported locales.
     *
     * @param string $name
     * @return string
     */
    function formatSeoUrl($name)
    {
        $url = strtolower($name);
        $url = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $url);
        $url = preg_replace("/[^a-z0-9_\s-]/", "", $url);
        $url = preg_replace("/[\s-]+/", " ", $url);
        $url = trim($url);
        return preg_replace("/[\s_]/", "-", $url);
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