<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;

trait IterationRules
{
    /**
     * Applies the given rule to every value of an array. If one of the array element doesn't comply with the rule, it
     * evaluates to false.
     *
     * @param Rule $rule
     * @param string $errorMessage
     * @return Rule
     */
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

    private static function nestedArray(string $key, array $rules, string $errorMessage = ""): Rule
    {
        $resultRule = new Rule();
        $resultRule->setErrorMessage($errorMessage);
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rules, $key, $errorMessage) {
            if (!is_object($data) && !is_array($data)) {
                return false;
            }
            if (is_array($data) && !isset($data[$key])) {
                return false;
            }
            if (is_object($data) && !property_exists($data, $key)) {
                return false;
            }
            if (!isAssociativeArray($rules)) {
                return false;
            }

            foreach ($rules as $fieldName => $nestedRule) {
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
