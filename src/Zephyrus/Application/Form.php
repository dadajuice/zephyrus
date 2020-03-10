<?php namespace Zephyrus\Application;

class Form
{
    private const TRIGGER_ALWAYS = 0;
    private const TRIGGER_NO_ERROR = 1;
    private const TRIGGER_FIELD_NO_ERROR = 2;
    private const SESSION_KEY = '_FIELDS';

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
        return (isset($_SESSION[self::SESSION_KEY][$fieldId]))
            ? $_SESSION[self::SESSION_KEY][$fieldId]
            : $defaultValue;
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
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $_SESSION[self::SESSION_KEY][$fieldId] = $value;
    }

    /**
     * Removes the specified fieldId from memory or clears the entire memorized
     * fields if not set.
     *
     * @param string $fieldId
     */
    public static function removeMemorizedValue($fieldId = null)
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            if (is_null($fieldId)) {
                $_SESSION[self::SESSION_KEY] = null;
                unset($_SESSION[self::SESSION_KEY]);
            } else {
                unset($_SESSION[self::SESSION_KEY][$fieldId]);
            }
        }
    }

    public function validate(string $field, Rule $rule, bool $optional = false)
    {
        $this->addRule($field, $rule, self::TRIGGER_ALWAYS, $optional);
    }

    public function validateWhenFieldHasNoError(string $field, Rule $rule, bool $optional = false)
    {
        $this->addRule($field, $rule, self::TRIGGER_FIELD_NO_ERROR, $optional);
    }

    public function validateWhenFormHasNoError(string $field, Rule $rule, bool $optional = false)
    {
        $this->addRule($field, $rule, self::TRIGGER_NO_ERROR, $optional);
    }

    /**
     * @return bool
     */
    public function verify(): bool
    {
        foreach ($this->rules as $validation) {
            $this->verifyAllRules($validation);
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

    public function removeField(string $parameterName)
    {
        if (isset($this->fields[$parameterName])) {
            unset($this->fields[$parameterName]);
            self::removeMemorizedValue($parameterName);
        }
    }

    public function addError(string $field, string $message)
    {
        $this->errors[] = (object) [
            'field' => $field,
            'message' => $message
        ];
    }

    public function hasError(?string $field = null): bool
    {
        if (is_null($field)) {
            return !empty($this->errors);
        }
        foreach ($this->errors as $error) {
            if ($error->field == $field) {
                return true;
            }
        }
        return false;
    }

    public function getErrors(): array
    {
        $errorsAssociated = [];
        foreach ($this->errors as $error) {
            if (!isset($errorsAssociated[$error->field])) {
                $errorsAssociated[$error->field] = [];
            }
            $errorsAssociated[$error->field][] = $error->message;
        }
        return $errorsAssociated;
    }

    public function getErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $error) {
            $messages[] = $error->message;
        }
        return $messages;
    }

    public function registerFeedback()
    {
        Feedback::error($this->getErrors());
    }

    public function getValue(string $field, $defaultValue = null)
    {
        return $this->fields[$field] ?? $defaultValue;
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
     * @return object
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

    private function verifyAllRules(\stdClass $validation)
    {
        if ($this->isValidationTriggered($validation->field, $validation)) {
            $result = $validation->rule->isValid($this->fields[$validation->field] ?? null, $this->fields);
            if (!$result) {
                $this->addError($validation->field, $validation->rule->getErrorMessage());
                self::removeMemorizedValue($validation->field);
            }
        }
    }

    private function isValidationTriggered(string $field, $validation): bool
    {
        if ($validation->trigger > self::TRIGGER_ALWAYS) {
            return !$this->hasError(($validation->trigger == self::TRIGGER_FIELD_NO_ERROR) ? $field : null);
        }
        return !$validation->optional || ($validation->optional && !empty($this->fields[$field]));
    }

    private function addRule(string $field, Rule $rule, int $trigger, bool $optional)
    {
        $validation = new \stdClass();
        $validation->rule = $rule;
        $validation->field = $field;
        $validation->trigger = $trigger;
        $validation->optional = $optional;
        $this->rules[] = $validation;
    }
}
