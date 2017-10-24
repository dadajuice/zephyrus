<?php namespace Zephyrus\Application;

class Form
{
    private const TRIGGER_ALWAYS = 0;
    private const TRIGGER_NO_ERROR = 1;
    private const TRIGGER_FIELD_NO_ERROR = 2;

    /**
     * @var array
     */
    private $rules = [];

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * Reads a memorized value for a given fieldId. If value has not been set the
     * specified default value is assigned (empty if not set). Excellent to set
     * remembered data in forms.
     *
     * @param string $fieldId
     * @param string $defaultValue
     * @return string
     */
    public static function readMemorizedValue($fieldId, $defaultValue = "")
    {
        return (isset($_SESSION['_FIELDS'][$fieldId])) ? $_SESSION['_FIELDS'][$fieldId] : $defaultValue;
    }

    /**
     * Memorizes the specified value for the given fieldId. Allows to be read by
     * the readMemorizedValue() function afterward.
     *
     * @param string $fieldId
     * @param string $value
     */
    public static function memorizeValue($fieldId, $value)
    {
        if (!isset($_SESSION['_FIELDS'])) {
            $_SESSION['_FIELDS'] = [];
        }
        $_SESSION['_FIELDS'][$fieldId] = $value;
    }

    /**
     * Removes the specified fieldId from memory or clears the entire memorized
     * fields if not set.
     *
     * @param string $fieldId
     */
    public static function removeMemorizedValue($fieldId = null)
    {
        if (isset($_SESSION['_FIELDS'])) {
            if (is_null($fieldId)) {
                $_SESSION['_FIELDS'] = null;
                unset($_SESSION['_FIELDS']);
            } else {
                unset($_SESSION['_FIELDS'][$fieldId]);
            }
        }
    }

    public function rule(string $field, Rule $rule)
    {
        $this->addRule($field, $rule, self::TRIGGER_ALWAYS);
    }

    public function ruleIfSafeField(string $field, Rule $rule)
    {
        $this->addRule($field, $rule, self::TRIGGER_FIELD_NO_ERROR);
    }

    public function ruleIfNoError(string $field, Rule $rule)
    {
        $this->addRule($field, $rule, self::TRIGGER_NO_ERROR);
    }

    /**
     * @return bool
     */
    public function verify(): bool
    {
        foreach ($this->rules as $field => $validations) {
            $this->verifyAllRules($field, $validations);
        }
        return empty($this->errors);
    }

    private function verifyAllRules(string $field, array $validations)
    {
        foreach ($validations as $validation) {
            if ($validation->trigger > self::TRIGGER_ALWAYS) {
                if ($validation->trigger == self::TRIGGER_NO_ERROR) {
                    if (!empty($this->errors)) {
                        continue;
                    }
                } elseif ($validation->trigger == self::TRIGGER_FIELD_NO_ERROR) {
                    if (isset($this->errors[$field])) {
                        continue;
                    }
                }
            }

            $result = $validation->rule->isValid($this->fields[$field], $this->fields);
            if (!$result) {
                $this->errors[$field][] = $validation->rule->getErrorMessage();
                self::removeMemorizedValue($field);
            }
        }
    }

    private function addRule(string $field, Rule $rule, int $trigger)
    {
        if (!array_key_exists($field, $this->fields)) {
            throw new \InvalidArgumentException("Specified field [$field] has not been registered in this form");
        }
        $validation = new \stdClass();
        $validation->rule = $rule;
        $validation->trigger = $trigger;
        $this->rules[$field][] = $validation;
    }

    /**
     * @param string[] $parameters
     */
    public function addFields(array $parameters)
    {
        foreach ($parameters as $parameterName => $value) {
            $this->addField($parameterName, $value);
        }
    }

    /**
     * @param string $parameterName
     * @param mixed $value
     */
    public function addField($parameterName, $value)
    {
        $this->fields[$parameterName] = $value;
        self::memorizeValue($parameterName, $value);
    }

    /**
     * @param string $field
     * @param string $message
     */
    public function addError($field, $message)
    {
        $this->errors[$field][] = $message;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string[]
     */
    public function getErrorMessages()
    {
        $results = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $message) {
                $results[] = $message;
            }
        }
        return $results;
    }

    public function getValue($field)
    {
        return $this->fields[$field];
    }

    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Tries to set values to the specified object using available setter
     * methods.
     *
     * @param object $obj
     */
    public function buildObject($obj)
    {
        foreach ($this->fields as $property => $value) {
            $method = 'set' . ucwords($property);
            if (is_callable([$obj, $method])) {
                $obj->{$method}($value);
            }
        }
    }
}
