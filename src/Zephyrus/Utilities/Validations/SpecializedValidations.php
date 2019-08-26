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
}
