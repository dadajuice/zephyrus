<?php namespace Zephyrus\Utilities\Validations;

trait TimeValidations
{
    public static function isDate($data): bool
    {
        $date = \DateTime::createFromFormat('Y-m-d', $data);
        return $date && $date->format('Y-m-d') == $data;
    }

    /**
     * Validates format HH:MM which ranges 00:00 - 11:59. Optionally, seconds
     * can be considered (HH:MM:SS) which then ranges 00:00:00 - 11:59:59.
     *
     * @param $data
     * @param bool $includeSeconds
     * @return bool
     */
    public static function isTime12Hours($data, bool $includeSeconds = false): bool
    {
        return preg_match("/^(1[012]|0[0-9]):([0-5][0-9])" . (($includeSeconds) ? ":([0-5][0-9])" : "") . "$/", $data);
    }

    /**
     * Validates format HH:MM which ranges 00:00 - 23:59. Optionally, seconds
     * can be considered (HH:MM:SS) which then ranges 00:00:00 - 23:59:59.
     *
     * @param $data
     * @param bool $includeSeconds
     * @return bool
     */
    public static function isTime24Hours($data, bool $includeSeconds = false): bool
    {
        return preg_match("/^(2[0-3]|[01][1-9]|10|00):([0-5][0-9])" . (($includeSeconds) ? ":([0-5][0-9])" : "") . "$/", $data);
    }

    public static function isDateTime12Hours($data, bool $includeSeconds = false): bool
    {
        list($datePart, $timePart) = explode(' ', $data);
        return self::isDate($datePart) && self::isTime12Hours($timePart, $includeSeconds);
    }

    public static function isDateTime24Hours($data, bool $includeSeconds = false): bool
    {
        list($datePart, $timePart) = explode(' ', $data);
        return self::isDate($datePart) && self::isTime24Hours($timePart, $includeSeconds);
    }
}
