<?php namespace Zephyrus\Utilities\Validations;

trait SpecializedValidations
{
    /**
     * Based on the North American Numbering Plan (NAPN).
     *
     * @see https://en.wikipedia.org/wiki/North_American_Numbering_Plan#Numbering_system
     */
    public static function isPhone($data): bool
    {
        return preg_match("/^([\(\+])?([0-9]{1,3}([\s])?)?([\+|\(|\-|\)|\s])?([0-9]{2,4})([\-|\)|\.|\s]([\s])?)?([0-9]{2,4})?([\.|\-|\s])?([0-9]{4,8})$/", $data);
    }

    public static function isUrl($data): bool
    {
        return preg_match("/^(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\\\".,<>?«»“”‘’]))$/", $data);
    }

    public static function isYoutubeUrl($data): bool
    {
        return preg_match("/^((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?$/", $data);
    }

    public static function isLiveUrl($data, array $acceptedValidCodes = [200, 201, 202, 204, 301, 302]): bool
    {
        $result = false;
        $url = filter_var($data, FILTER_VALIDATE_URL);
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_CONNECTTIMEOUT => 1
        ]);
        curl_exec($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        if (in_array($httpCode, $acceptedValidCodes)) {
            $result = true;
        }
        curl_close($handle);
        return $result;
    }

    /**
     * Validates United States postal code five-digit and nine-digit (called ZIP+4) formats.
     *
     * @see https://www.oreilly.com/library/view/regular-expressions-cookbook/9781449327453/ch04s14.html
     */
    public static function isZipCode($data): bool
    {
        return preg_match("/^[0-9]{5}(?:-[0-9]{4})?$/", $data);
    }

    /**
     * Validates Canadian postal code format (insensitive). The characters are arranged in the
     * form ‘ANA NAN’, where ‘A’ represents an alphabetic character and ‘N’ represents a numeric
     * character (e.g., K1A 0T6). The postal code uses 18 alphabetic characters and 10 numeric
     * characters. Postal codes do not include the letters D, F, I, O, Q or U, and the first
     * position also does not make use of the letters W or Z.
     *
     * @param $data
     * @return bool
     */
    public static function isPostalCode($data): bool
    {
        return preg_match("/^[ABCEGHJ-NPRSTVXY][0-9][ABCEGHJ-NPRSTV-Z] ?[0-9][ABCEGHJ-NPRSTV-Z][0-9]$/", strtoupper($data));
    }

    public static function isIPv4($data, bool $includeReserved = true, bool $includePrivate = true): bool
    {
        $flag = FILTER_FLAG_IPV4
            | ((!$includeReserved) ? FILTER_FLAG_NO_RES_RANGE : 0)
            | ((!$includePrivate) ? FILTER_FLAG_NO_PRIV_RANGE : 0);
        return filter_var($data, FILTER_VALIDATE_IP, $flag) !== false;
    }

    public static function isIPv6($data, bool $includeReserved = true, bool $includePrivate = true): bool
    {
        $flag = FILTER_FLAG_IPV6
            | ((!$includeReserved) ? FILTER_FLAG_NO_RES_RANGE : 0)
            | ((!$includePrivate) ? FILTER_FLAG_NO_PRIV_RANGE : 0);
        return filter_var($data, FILTER_VALIDATE_IP, $flag) !== false;
    }

    public static function isIpAddress($data, bool $includeReserved = true, bool $includePrivate = true): bool
    {
        $flag = (FILTER_FLAG_IPV6 | FILTER_FLAG_IPV4)
            | ((!$includeReserved) ? FILTER_FLAG_NO_RES_RANGE : 0)
            | ((!$includePrivate) ? FILTER_FLAG_NO_PRIV_RANGE : 0);
        return filter_var($data, FILTER_VALIDATE_IP, $flag) !== false;
    }

    /**
     * Validates a correctly formed JSON string. Will rejects string that contains only number, string or boolean value
     * even if they are technically valid JSON representation. This validation is to ensure to have a valid JSON object
     * or array structure as it would be the basic usage of validation for JSON.
     *
     * @param $data
     * @return bool
     */
    public static function isJson($data): bool
    {
        $json = json_decode($data);
        return $json && $data != $json;
    }

    /**
     * Validates a correctly formed XML string that should be done before a parsing.
     *
     * @param $data
     * @return bool
     */
    public static function isXml($data): bool
    {
        $xml = @simplexml_load_string($data);
        return (bool) $xml;
    }
}
