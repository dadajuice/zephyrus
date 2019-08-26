<?php namespace Zephyrus\Utilities\Validations;

trait TimeValidations
{
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

    public static function isDateTime12Hours($data): bool
    {
        list($datePart, $timePart) = explode(' ', $data);
        return self::isDate($datePart) && self::isTime12Hours($timePart);
    }

    public static function isDateTime24Hours($data): bool
    {
        list($datePart, $timePart) = explode(' ', $data);
        return self::isDate($datePart) && self::isTime24Hours($timePart);
    }
}
