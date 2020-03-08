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
     * is alpha with numbers.
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

    public static function isRegex($data, $regex): bool
    {
        return preg_match('/^' . $regex . '$/', $data);
    }
}
