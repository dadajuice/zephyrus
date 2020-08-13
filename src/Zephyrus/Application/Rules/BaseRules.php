<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait BaseRules
{
    public static function decimal(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned) ? ['Zephyrus\Utilities\Validation', 'isDecimal'] : ['Zephyrus\Utilities\Validation', 'isSignedDecimal'], $errorMessage);
    }

    public static function integer(string $errorMessage = "", $allowSigned = false): Rule
    {
        return new Rule((!$allowSigned) ? ['Zephyrus\Utilities\Validation', 'isInteger'] : ['Zephyrus\Utilities\Validation', 'isSignedInteger'], $errorMessage);
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

    public static function array(string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) {
            return is_array($data);
        }, $errorMessage);
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

    public static function all(Rule $rule, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data, $fields) use ($rule) {
            if (!is_array($data)) {
                return false;
            }
            $filteredArray = array_filter($data, function ($value) use ($rule, $fields) {
                return $rule->isValid($value, $fields);
            });
            return count($filteredArray) == count($data);
        }, $errorMessage);
    }

    public static function onlyWithin(array $possibleValues, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($possibleValues) {
            return Validation::isOnlyWithin($data, $possibleValues);
        }, $errorMessage);
    }
}
