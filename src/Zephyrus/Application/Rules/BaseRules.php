<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;
use Zephyrus\Utilities\Validations\ValidationCallback;

trait BaseRules
{
    public static function decimal(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned) ? ValidationCallback::DECIMAL : ValidationCallback::DECIMAL_SIGNED, $errorMessage);
    }

    public static function integer(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned) ? ValidationCallback::INTEGER : ValidationCallback::INTEGER_SIGNED, $errorMessage);
    }

    public static function range($min, $max, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($min, $max) {
            return Validation::isInRange($data, $min, $max);
        }, $errorMessage);
    }

    public static function inArray(array $array, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($array) {
            return in_array($data, $array);
        }, $errorMessage);
    }

    public static function sameAs(string $comparedFieldName, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data, $values) use ($comparedFieldName) {
            return isset($values[$comparedFieldName]) && $data == $values[$comparedFieldName];
        }, $errorMessage);
    }

    public static function array(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_array($data);
        }, $errorMessage);
    }

    public static function boolean(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::BOOLEAN, $errorMessage);
    }

    public static function regex($pattern, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($pattern) {
            return Validation::isRegex($data, $pattern);
        }, $errorMessage);
    }
}
