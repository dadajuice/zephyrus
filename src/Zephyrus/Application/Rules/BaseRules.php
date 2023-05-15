<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait BaseRules
{
    /**
     * Field is required for submission, meaning its value is not empty (empty string ['', ""], null and false). Zero
     * values are considered okay in this context, e.g. 0, '0', 0.0, '0.0' would pass the required validation.
     *
     * @param string $errorMessage
     * @return Rule
     */
    public static function required(string $errorMessage = ""): Rule
    {
        return new Rule(function (mixed $data) {
            if (is_numeric($data)) {
                return true;
            }
            return !empty(is_string($data) ? trim($data) : $data);
        }, $errorMessage, "required");
    }

    public static function decimal(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned)
            ? ['Zephyrus\Utilities\Validation', 'isDecimal']
            : ['Zephyrus\Utilities\Validation', 'isSignedDecimal'], $errorMessage, 'decimal');
    }

    public static function integer(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned)
            ? ['Zephyrus\Utilities\Validation', 'isInteger']
            : ['Zephyrus\Utilities\Validation', 'isSignedInteger'], $errorMessage, 'integer');
    }

    public static function range(int $min, int $max, string $errorMessage = ""): Rule
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

    public static function notInArray(array $array, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($array) {
            return !in_array($data, $array);
        }, $errorMessage);
    }

    public static function sameAs(string $comparedFieldName, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data, $values) use ($comparedFieldName) {
            return isset($values[$comparedFieldName]) && $data == $values[$comparedFieldName];
        }, $errorMessage);
    }

    public static function associativeArray(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return Validation::isAssociativeArray($data);
        }, $errorMessage);
    }

    public static function array(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_array($data);
        }, $errorMessage, 'array');
    }

    public static function object(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_object($data);
        }, $errorMessage, 'object');
    }

    public static function arrayNotEmpty(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_array($data) && !empty($data);
        }, $errorMessage);
    }

    public static function boolean(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isBoolean'], $errorMessage);
    }

    public static function regex(string $pattern, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($pattern) {
            return Validation::isRegex($data, $pattern);
        }, $errorMessage);
    }

    public static function onlyWithin(array $possibleValues, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($possibleValues) {
            return Validation::isOnlyWithin($data, $possibleValues);
        }, $errorMessage);
    }
}
