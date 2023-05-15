<?php namespace Zephyrus\Application\Rules;

use InvalidArgumentException;
use Zephyrus\Application\Rule;

trait IterationRules
{
    /**
     * Applies the given rule to every value of an array. If one of the array element doesn't comply with the rule, it
     * evaluates to false.
     *
     * @param array $rules
     * @return Rule
     */
    public static function each(array $rules): Rule
    {
        return self::iteration($rules);
    }

    /**
     * Applies the given rule to every key of an array. If one of the array kay doesn't comply with the rule, it
     * evaluates to false.
     *
     * @param array $rules
     * @return Rule
     */
    public static function eachKey(array $rules): Rule
    {
        return self::iteration($rules, 'keys');
    }

    /**
     * Allows to add one or multiple rules to a nested child element (either an array or an object).
     *
     * @param string $key
     * @param Rule[] $rules
     * @return Rule
     */
    public static function nested(string $key, array $rules): Rule
    {
        $resultRule = new Rule(null, "", "nested");
        foreach ($rules as $rule) {
            if (!($rule instanceof Rule)) {
                throw new InvalidArgumentException("Rules argument for [nested] must be an array of Rule instances.");
            }
        }
        $resultRule->iterator = true;
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rules, $key) {
            if (!is_object($data) && !is_array($data)) {
                throw new InvalidArgumentException("Data argument for the [nested] rule must either be an associative array or an object. Consider adding a Rule::associativeArray or Rule::object beforehand.");
            }
            if (is_array($data) && !isset($data[$key])) {
                $data[$key] = null;
            }
            if (is_object($data) && !property_exists($data, $key)) {
                $data->$key = null;
            }

            $hasError = false;
            foreach ($rules as $rule) {
                if ($rule->isIterator()) {
                    $rule->setFieldName($key);
                } else {
                    $rule->setPathing($key);
                }
                if (!$rule->isValid(is_array($data) ? $data[$key] : $data->$key, $fields)) {
                    $resultRule->triggerError($rule);
                    $hasError = true;
                    if (!$rule->isIterator()) {
                        break;
                    }
                }
            }
            return !$hasError;
        });
        return $resultRule;
    }

    /**
     * Applies the given rules to every key or value of an array depending on the selected mode.
     *
     * @param array $rules
     * @param string $mode
     * @return Rule
     */
    private static function iteration(array $rules, string $mode = 'values'): Rule
    {
        $resultRule = new Rule(null, "", ($mode == 'values') ? "each" : "eachKey");
        foreach ($rules as $rule) {
            if (!($rule instanceof Rule)) {
                throw new InvalidArgumentException("Rules argument for [each] must be an array of Rule instances.");
            }
        }
        $resultRule->iterator = true;
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rules, $mode) {
            if (!is_array($data)) {
                throw new InvalidArgumentException("Data argument for the [each] rule must be an array. Consider adding a Rule::array beforehand.");
            }
            $hasError = false;
            foreach ($data as $key => $value) {
                foreach ($rules as $rule) {
                    if ($rule->isIterator()) {
                        $rule->setFieldName($key);
                    } else {
                        $rule->setPathing($key);
                    }
                    if (!$rule->isValid(($mode == 'values') ? $value : $key, $fields)) {
                        $resultRule->triggerError($rule);
                        $hasError = true;
                        if (!$rule->isIterator()) {
                            break;
                        }
                    }
                }
            }
            return !$hasError;
        });
        return $resultRule;
    }
}
