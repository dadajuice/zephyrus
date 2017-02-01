<?php

namespace Zephyrus\Utilities\Validations;

class NumericValidation extends BaseValidation
{
    public static function isDecimal($data): bool
    {
        return self::isRegexValid($data, "[0-9]+((\.|,)[0-9]+)?");
    }

    public static function isInteger($data): bool
    {
        return self::isRegexValid($data, '[0-9]+');
    }

    public static function isSignedDecimal($data): bool
    {
        return self::isRegexValid($data, "-?[0-9]+((\.|,)[0-9]+)?");
    }

    public static function isSignedInteger($data): bool
    {
        return self::isRegexValid($data, '-?[0-9]+');
    }
}
