<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait IterationRules
{
    /**
     * Applies the given rule to every value of an array. If one of the array element doesn't comply with the rule, it
     * evaluates to false.
     *
     * @param Rule|array $rule
     * @param string $errorMessage
     * @return Rule
     */
    public static function all(Rule|array $rule, string $errorMessage = ""): Rule
    {
        if (is_array($rule) && Validation::isAssociativeArray($rule)) {
            return self::allNested($rule, $errorMessage);
        }
        return self::allSingleRule($rule, $errorMessage);
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
        if (is_array($rule) && Validation::isAssociativeArray($rule)) {
            return self::nestedArray($key, $rule, $errorMessage);
        }
        return self::nestedRule($key, $rule, $errorMessage);
    }

    private static function nestedRule(string $key, Rule|array $rule, string $errorMessage = ""): Rule
    {
        $resultRule = new Rule();
        $resultRule->setErrorMessage($errorMessage);
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rule, $key) {
            if (!is_object($data) && !is_array($data)) {
                return false;
            }
            if (is_array($data) && !isset($data[$key])) {
                return false;
            }
            if (is_object($data) && !property_exists($data, $key)) {
                return false;
            }

            $rulesToValidate = [];
            if (!is_array($rule)) {
                $rulesToValidate[] = $rule;
            } else {
                $rulesToValidate = $rule;
            }

            foreach ($rulesToValidate as $ruleToValidate) {
                if (!($ruleToValidate instanceof Rule)) {
                    return false;
                }
                $valid = $ruleToValidate->isValid(is_array($data) ? $data[$key] : $data->$key, $fields);
                if (!$valid) {
                    $pathing = $ruleToValidate->getPathing();
                    if (!empty($pathing)) {
                        $pathing = '.' . $pathing;
                    }
                    $resultRule->setPathing($key . $pathing);
                    $resultRule->setErrorMessage($ruleToValidate->getErrorMessage());
                    return $valid;
                }
            }
            return true;
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
            if (!Validation::isAssociativeArray($rules)) {
                return false;
            }

            foreach ($rules as $fieldName => $nestedRule) {
                if (Validation::isAssociativeArray($nestedRule)) {
                    $innerNestedRule = self::nested($fieldName, $nestedRule, $errorMessage);
                    $valid = $innerNestedRule->isValid(is_array($data) ? $data[$key] : $data->$key, $fields);
                    if (!$valid) {
                        $pathing = $innerNestedRule->getPathing();
                        if (!empty($pathing)) {
                            $pathing = '.' . $pathing;
                        }
                        $resultRule->setPathing($key . $pathing);
                        $resultRule->setErrorMessage($innerNestedRule->getErrorMessage());
                        return false;
                    }
                } else {
                    // Either Rule or array of Rules (no assoc)
                    $rulesToValidate = [];
                    if (!is_array($nestedRule)) {
                        $rulesToValidate[] = $nestedRule;
                    } else {
                        $rulesToValidate = $nestedRule;
                    }

                    foreach ($rulesToValidate as $ruleToValidate) {
                        if (!($ruleToValidate instanceof Rule)) {
                            return false;
                        }
                        $nestedRule = self::nested($fieldName, $ruleToValidate, $errorMessage);
                        $valid = $nestedRule->isValid(is_array($data) ? $data[$key] : $data->$key, $fields);
                        if (!$valid) {
                            $pathing = $nestedRule->getPathing();
                            if (!empty($pathing)) {
                                $pathing = '.' . $pathing;
                            }
                            $resultRule->setPathing($key . $pathing);
                            $resultRule->setErrorMessage($nestedRule->getErrorMessage());
                            return false;
                        }
                    }
                }
            }

            return true;
        });

        return $resultRule;
    }

    private static function allSingleRule(Rule|array $rule, string $errorMessage = ""): Rule
    {
        $resultRule = new Rule();
        $resultRule->setErrorMessage($errorMessage);
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rule) {
            if (!is_array($data)) {
                return false;
            }

            $rulesToValidate = [];
            if (!is_array($rule)) {
                $rulesToValidate[] = $rule;
            } else {
                $rulesToValidate = $rule;
            }

            foreach ($data as $key => $value) {
                foreach ($rulesToValidate as $ruleToValidate) {
                    if (!($ruleToValidate instanceof Rule)) {
                        return false;
                    }

                    $valid = $ruleToValidate->isValid($value, $fields);
                    if (!$valid) {
                        $pathing = $ruleToValidate->getPathing();
                        if (!empty($pathing)) {
                            $pathing = '.' . $pathing;
                        }
                        $resultRule->setPathing($key . $pathing);
                        $resultRule->setErrorMessage($ruleToValidate->getErrorMessage());
                        return false;
                    }
                }
            }
            return true;
        });
        return $resultRule;
    }

    private static function allNested(array $rules, string $errorMessage = ""): Rule
    {
        $resultRule = new Rule();
        $resultRule->setErrorMessage($errorMessage);
        $resultRule->setValidationCallback(function ($data, $fields) use ($resultRule, $rules, $errorMessage) {
            if (!is_array($data)) {
                return false;
            }
            if (!Validation::isAssociativeArray($rules)) {
                return false;
            }

            foreach ($data as $key => $value) {
                foreach ($rules as $field => $rule) {
                    $rulesToValidate = [];
                    if (!is_array($rule)) {
                        $rulesToValidate[] = $rule;
                    } else {
                        $rulesToValidate = $rule;
                    }
                    foreach ($rulesToValidate as $ruleToValidate) {
                        if (!($ruleToValidate instanceof Rule)) {
                            return false;
                        }
                        $innerRule = self::nested($field, $ruleToValidate, $errorMessage);
                        $valid = $innerRule->isValid($value, $fields);
                        if (!$valid) {
                            $pathing = $innerRule->getPathing();
                            if (!empty($pathing)) {
                                $pathing = '.' . $pathing;
                            }
                            $resultRule->setPathing($key . $pathing);
                            $resultRule->setErrorMessage($innerRule->getErrorMessage());
                            return false;
                        }
                    }
                }
            }
            return true;
        });
        return $resultRule;
    }
}
