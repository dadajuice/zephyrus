<?php namespace Zephyrus\Utilities\Validations;

trait StringValidations
{
    public static function isNotEmpty($data): bool
    {
        return !empty(is_string($data) ? trim($data) : $data);
    }

    public static function isAlpha($data, bool $considerAccentedChar = true): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        return preg_match("/^[a-zA-Z" . (($considerAccentedChar) ? $accentedChar : '') . "]+$/", $data);
    }

    /**
     * Validates if the given string's length is under or equal the specified max length.
     *
     * @param $data
     * @param int $maxLength
     * @return bool
     */
    public static function isMaxLength($data, int $maxLength): bool
    {
        return strlen($data) <= $maxLength;
    }

    /**
     * Validates if the given string's length is over or equal the specified max length.
     *
     * @param $data
     * @param int $minLength
     * @return bool
     */
    public static function isMinLength($data, int $minLength): bool
    {
        return strlen($data) >= $minLength;
    }

    /**
     * Is alpha with numbers.
     *
     * @param $data
     * @param bool $considerAccentedChar
     * @return bool
     */
    public static function isAlphanumeric($data, bool $considerAccentedChar = true): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        return preg_match('/^[a-zA-Z0-9' . (($considerAccentedChar) ? $accentedChar : '') . ']+$/', $data);
    }

    /**
     * Is alpha with punctuations.
     *
     * @param $data
     * @return bool
     */
    public static function isName($data): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        $punctuationChar = "- '";
        return preg_match("/^[a-zA-Z" . $punctuationChar . $accentedChar . "]+$/", $data);
    }

    /**
     * Validates that the given string is compliant with basic password
     * requirements which are : at least one uppercase, at least one
     * lowercase, at least one number and having minimum a length of
     * 8 characters.
     *
     * @param $data
     * @return bool
     */
    public static function isPasswordCompliant($data): bool
    {
        $uppercase = preg_match('@[A-Z]@', $data);
        $lowercase = preg_match('@[a-z]@', $data);
        $number = preg_match('@[0-9]@', $data);
        return strlen($data) >= 8 && $uppercase && $lowercase && $number;
    }

    public static function isEmail($data): bool
    {
        return filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validates that the given string is compliant with PHP variable naming convention as defined in the official
     * documentation (https://www.php.net/manual/en/language.variables.basics.php).
     *
     * @param $data
     * @return bool
     */
    public static function isVariable($data): bool
    {
        return preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $data);
    }

    public static function isRegex($data, $regex): bool
    {
        return preg_match('/^' . $regex . '$/', $data);
    }
}
