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

    /**
     * Allows to add one or multiple rules to a nested child element (either an array or an object). The given error
     * message for the nested rule is only used when something is wrong with the validated data (e.g. not an array or
     * key doesn't exist).
     *
     * @param string $key
     * @param Rule|array $rule
     * @param string $errorMessage
     * @return Rule
     */
    public static function nested(string $key, Rule|array $rule, string $errorMessage = ""): Rule
    {
        if ($rule instanceof Rule) {
            return self::nestedRule($key, $rule, $errorMessage);
        }
        return self::nestedArray($key, $rule, $errorMessage);
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

    private static function nestedRule(string $key, Rule $rule, string $errorMessage = ""): Rule
    {
        $resultRule = new Rule();
        $resultRule->setErrorMessage($errorMessage);
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rule, $key, $errorMessage) {
            if (!is_object($data) && !is_array($data)) {
                return false;
            }
            if (is_array($data) && !isset($data[$key])) {
                return false;
            }
            if (is_object($data) && !property_exists($data, $key)) {
                return false;
            }

            $valid = $rule->isValid(is_array($data) ? $data[$key] : $data->$key, $fields);
            if (!$valid) {
                $resultRule->setErrorMessage($rule->getErrorMessage());
            }
            return $valid;

        });
        return $resultRule;
    }

    private static function nestedArray(string $key, array $rule, string $errorMessage = ""): Rule
    {
        $resultRule = new Rule();
        $resultRule->setErrorMessage($errorMessage);
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rule, $key, $errorMessage) {
            if (!is_object($data) && !is_array($data)) {
                return false;
            }
            if (is_array($data) && !isset($data[$key])) {
                return false;
            }
            if (is_object($data) && !property_exists($data, $key)) {
                return false;
            }
            if (!isAssociativeArray($rule)) {
                return false;
            }

            foreach ($rule as $fieldName => $nestedRule) {
                if ($nestedRule instanceof Rule) {
                    $nestedRule = self::nested($fieldName, $nestedRule, $errorMessage);
                    $valid = $nestedRule->isValid(is_array($data) ? $data[$key] : $data->$key, $fields);
                    if (!$valid) {
                        $resultRule->setErrorMessage($nestedRule->getErrorMessage());
                        return false;
                    }
                } elseif (isAssociativeArray($nestedRule)) {
                    $innerNestedRule = self::nested($fieldName, $nestedRule, $errorMessage);
                    $valid = $innerNestedRule->isValid(is_array($data) ? $data[$key] : $data->$key, $fields);
                    if (!$valid) {
                        $resultRule->setErrorMessage($innerNestedRule->getErrorMessage());
                        return false;
                    }
                }
            }

            return true;
        });

        return $resultRule;
    }
}
