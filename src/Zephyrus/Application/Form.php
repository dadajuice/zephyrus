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
     * @param mixed $defaultValue
     * @return mixed
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
     * @param mixed $value
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

    public function validate(string $field, Rule $rule)
    {
        $this->addRule($field, $rule, self::TRIGGER_ALWAYS);
    }

    public function validateWhenFieldHasNoError(string $field, Rule $rule)
    {
        $this->addRule($field, $rule, self::TRIGGER_FIELD_NO_ERROR);
    }

    public function validateWhenFormHasNoError(string $field, Rule $rule)
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

    public function addFields(array $parameters)
    {
        foreach ($parameters as $parameterName => $value) {
            $this->addField($parameterName, $value);
        }
    }

    public function addField(string $parameterName, $value)
    {
        $this->fields[$parameterName] = $value;
        self::memorizeValue($parameterName, $value);
    }

    public function addError(string $field, string $message)
    {
        $this->errors[$field][] = $message;
    }

    public function hasError(?string $field = null): bool
    {
        if (is_null($field)) {
            return !empty($this->errors);
        }
        return isset($this->errors[$field]);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorMessages(): array
    {
        $results = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $message) {
                $results[] = $message;
            }
        }
        return $results;
    }

    public function getValue(string $field)
    {
        return $this->fields[$field] ?? null;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function isRegistered(string $field): bool
    {
        return isset($this->fields[$field]);
    }

    /**
     * Tries to set values to the specified object using available setter
     * methods.
     *
     * @param object $instance
     */
    public function buildObject($instance = null)
    {
        if (is_null($instance)) {
            return (object) $this->fields;
        }
        foreach ($this->fields as $property => $value) {
            $method = 'set' . ucwords($property);
            if (is_callable([$instance, $method])) {
                $instance->{$method}($value);
            }
        }
    }

    private function verifyAllRules(string $field, array $validations)
    {
        foreach ($validations as $validation) {
            if ($this->isValidationTriggered($field, $validation)) {
                $result = $validation->rule->isValid($this->fields[$field] ?? null, $this->fields);
                if (!$result) {
                    $this->errors[$field][] = $validation->rule->getErrorMessage();
                    self::removeMemorizedValue($field);
                }
            }
        }
    }

    private function isValidationTriggered(string $field, $validation): bool
    {
        if ($validation->trigger > self::TRIGGER_ALWAYS) {
            return !$this->hasError(($validation->trigger == self::TRIGGER_FIELD_NO_ERROR) ? $field : null);
        }
        return true;
    }

    private function addRule(string $field, Rule $rule, int $trigger)
    {
        $validation = new \stdClass();
        $validation->rule = $rule;
        $validation->trigger = $trigger;
        $this->rules[$field][] = $validation;
    }
}
