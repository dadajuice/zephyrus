<?php namespace Zephyrus\Utilities\Validations;

trait BaseValidations
{
    public static function isDecimal($data): bool
    {
        return preg_match("/^[0-9]+((\.|,)[0-9]+)?$/", $data);
    }

    public static function isInteger($data): bool
    {
        return preg_match("/^[0-9]+$/", $data);
    }

    public static function isSignedDecimal($data): bool
    {
        return preg_match("/^-?[0-9]+((\.|,)[0-9]+)?$/", $data);
    }

    public static function isSignedInteger($data): bool
    {
        return preg_match("/^-?[0-9]+$/", $data);
    }

    public static function isInRange($data, $min, $max): bool
    {
        return ($min <= $data) && ($data <= $max);
    }

    public static function isBoolean($data): bool
    {
        return is_bool($data)
            || strcasecmp($data, "true") == 0
            || strcasecmp($data, "false") == 0
            || (is_int($data) && $data == 0)
            || (is_int($data) && $data == 1);
    }

    public static function isOnlyWithin($data, array $allPossibleValues)
    {
        if (!is_array($data)) {
            return in_array($data, $allPossibleValues);
        }
        return !array_diff($data, $allPossibleValues);
    }
}
