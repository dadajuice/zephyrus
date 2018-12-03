<?php namespace Zephyrus\Utilities\Validations;

class BaseValidation
{
    public static function isNotEmpty($data): bool
    {
        return !empty(is_string($data) ? trim($data) : $data);
    }

    public static function isAlpha($data): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        return self::isRegexValid($data, "[a-zA-Z" . $accentedChar . "]+");
    }

    public static function isName($data): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        return self::isRegexValid($data, "[a-zA-Z- " . $accentedChar . "]+");
    }

    public static function isAlphanumeric($data): bool
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $data);
    }

    public static function isPasswordCompliant($data): bool
    {
        $uppercase = preg_match('@[A-Z]@', $data);
        $lowercase = preg_match('@[a-z]@', $data);
        $number = preg_match('@[0-9]@', $data);
        return strlen($data) >= 8 && $uppercase && $lowercase && $number;
    }

    public static function isEmail($data): bool
    {
        return filter_var($data, FILTER_VALIDATE_EMAIL);
    }

    public static function isDate($data): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $data);
        return $date && $date->format('Y-m-d') == $data;
    }

    /**
     * Validates format HH:MM which ranges 00:00 - 11:59.
     *
     * @param $data
     * @return bool
     */
    public static function isTime12Hours($data): bool
    {
        return preg_match("/^(1[012]|0[0-9]):([0-5][0-9])$/", $data);
    }

    /**
     * Validates format HH:MM which ranges 00:00 - 23:59.
     *
     * @param $data
     * @return bool
     */
    public static function isTime24Hours($data): bool
    {
        return preg_match("/^(2[0-3]|[01][1-9]|10|00):([0-5][0-9])$/", $data);
    }

    /**
     * Based on the North American Numbering Plan (NAPN).
     *
     * @see https://en.wikipedia.org/wiki/North_American_Numbering_Plan#Numbering_system
     */
    public static function isPhone($data): bool
    {
        return self::isRegexValid($data, "([\(\+])?([0-9]{1,3}([\s])?)?([\+|\(|\-|\)|\s])?([0-9]{2,4})([\-|\)|\.|\s]([\s])?)?([0-9]{2,4})?([\.|\-|\s])?([0-9]{4,8})");
    }

    public static function isUrl($data): bool
    {
        return self::isRegexValid($data, "(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\\\".,<>?«»“”‘’]))");
    }

    public static function isYoutubeUrl($data): bool
    {
        return self::isRegexValid($data, "((?:https?:)?\/\/)?((?:www|m)\.)?((?:youtube\.com|youtu.be))(\/(?:[\w\-]+\?v=|embed\/|v\/)?)([\w\-]+)(\S+)?");
    }

    /**
     * Validates United States postal code five-digit and nine-digit (called ZIP+4) formats.
     *
     * @see https://www.oreilly.com/library/view/regular-expressions-cookbook/9781449327453/ch04s14.html
     */
    public static function isZipCode($data): bool
    {
        return self::isRegexValid($data, "[0-9]{5}(?:-[0-9]{4})?");
    }

    public static function isRegexValid($data, $regex): bool
    {
        return preg_match('/^' . $regex . '$/', $data);
    }
}
