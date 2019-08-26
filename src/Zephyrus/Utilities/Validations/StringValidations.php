<?php namespace Zephyrus\Utilities\Validations;

trait StringValidations
{
    public static function isNotEmpty($data): bool
    {
        return !empty(is_string($data) ? trim($data) : $data);
    }

    public static function isAlpha($data): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        return preg_match("/^[a-zA-Z" . $accentedChar . "]+$/", $data);
    }

    public static function isName($data): bool
    {
        $accentedChar = "àèìòùÀÈÌÒÙáéíóúýÁÉÍÓÚÝâêîôûÂÊÎÔÛãñõÃÑÕäëïöüÿÄËÏÖÜŸçÇßØøÅåÆæœ";
        return preg_match("/^[a-zA-Z- " . $accentedChar . "]+$/", $data);
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

    public static function isRegex($data, $regex): bool
    {
        return preg_match('/^' . $regex . '$/', $data);
    }
}
