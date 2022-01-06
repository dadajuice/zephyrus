<?php namespace Zephyrus\Application;

use stdClass;

class Form
{
    private const SESSION_KEY = '_FIELDS';

    /**
     * @var FormField[]
     */
    private $fields = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @deprecated
     * @var bool
     */
    private $optionalOnEmpty = true;

    /**
     * Reads a memorized value for a given fieldId. If value has not been set the specified default value is
     * assigned (empty if not set). Excellent to set remembered data in forms.
     *
     * @param string $fieldId
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function readMemorizedValue(string $fieldId, $defaultValue = "")
    {
        if (session_status() == PHP_SESSION_NONE) {
            return $defaultValue;
        }
        return (isset($_SESSION[self::SESSION_KEY][$fieldId]))
            ? $_SESSION[self::SESSION_KEY][$fieldId]
            : $defaultValue;
    }

    /**
     * Memorizes the specified value for the given fieldId. Allows to be read by the readMemorizedValue() function
     * afterward.
     *
     * @param string $fieldId
     * @param mixed $value
     */
    public static function memorizeValue(string $fieldId, $value)
    {
        if (session_status() == PHP_SESSION_NONE) {
            return;
        }
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        $_SESSION[self::SESSION_KEY][$fieldId] = $value;
    }

    /**
     * Removes the specified fieldId from memory or clears the entire memorized fields if not set.
     *
     * @param string|null $fieldId
     */
    public static function removeMemorizedValue(?string $fieldId = null)
    {
        if (session_status() == PHP_SESSION_NONE) {
            return;
        }
        if (isset($_SESSION[self::SESSION_KEY])) {
            if (is_null($fieldId)) {
                $_SESSION[self::SESSION_KEY] = null;
                unset($_SESSION[self::SESSION_KEY]);
            } else {
                unset($_SESSION[self::SESSION_KEY][$fieldId]);
            }
        }
    }

    /**
     * Retrieves a field from the form. If the field doesn't exist, a corresponding field will be added to the form data
     * with a NULL value. Useful to validate a required checkbox for example.
     *
     * @param string $name
     * @return FormField
     */
    public function field(string $name): FormField
    {
        if (!isset($this->fields[$name])) {
            $this->addField($name, null);
        }
        return $this->fields[$name];
    }

    /**
     * Old ways of validations. Should now use field($name)->validate(Rule $rule).
     *
     * @deprecated
     * @param string $field
     * @param Rule $rule
     * @param bool $optional
     */
    public function validate(string $field, Rule $rule, bool $optional = false)
    {
        $this->field($field)->validate($rule, $optional);
    }

    /**
     * Old ways of validations. Should now use field($name)->validate(Rule $rule).
     *
     * @deprecated
     * @param string $field
     * @param Rule $rule
     * @param bool $optional
     */
    public function validateWhenFieldHasNoError(string $field, Rule $rule, bool $optional = false)
    {
        $this->field($field)->validate($rule, $optional);
    }

    /**
     * Old ways of validations. Should now use field($name)->validate(Rule $rule).
     *
     * @deprecated
     * @param string $field
     * @param Rule $rule
     * @param bool $optional
     */
    public function validateWhenFormHasNoError(string $field, Rule $rule, bool $optional = false)
    {
        $this->field($field)->validate($rule, $optional);
    }

    /**
     * @return bool
     */
    public function verify(): bool
    {
        foreach ($this->fields as $name => $field) {
            if (!$field->verify($this->getFields())) {
                foreach ($field->getErrorMessages() as $message) {
                    $this->addError($name, $message);
                }
                self::removeMemorizedValue($name);
            }
        }
        return empty($this->errors);
    }

    /**
     * Inserts a list of fields into the form data. Parameters is an associative array where the keys are the field name
     * and the value the corresponding values.
     *
     * @param array $parameters
     */
    public function addFields(array $parameters)
    {
        foreach ($parameters as $parameterName => $value) {
            $this->addField($parameterName, $value);
        }
    }

    /**
     * Inserts a field into the form data.
     *
     * @param string $name
     * @param $value
     */
    public function addField(string $name, $value)
    {
        $this->fields[$name] = new FormField($name, $value);
        // Remove once the optionalOnEmpty is no longer in this class
        $this->fields[$name]->setOptionalOnEmpty($this->optionalOnEmpty);
        self::memorizeValue($name, $value);
    }

    /**
     * Removes the specified field from the form data.
     *
     * @param string $field
     */
    public function removeField(string $field)
    {
        if (isset($this->fields[$field])) {
            unset($this->fields[$field]);
            self::removeMemorizedValue($field);
        }
    }

    /**
     * Adds an error to the form for the specified field.
     *
     * @param string $field
     * @param string $message
     */
    public function addError(string $field, string $message)
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Verifies if the form has error for a specified field or the entire form (if no field is provided).
     *
     * @param string|null $field
     * @return bool
     */
    public function hasError(?string $field = null): bool
    {
        if (is_null($field)) {
            return !empty($this->errors);
        }
        return isset($this->errors[$field]);
    }

    /**
     * Retrieves all the registered errors of the form in an associative array which the keys are the field name and the
     * values are an array of error messages.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Retrieves a simple array containing only the error messages (without field indications).
     *
     * @return array
     */
    public function getErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Automatically prepare the feedbacks for the form fields with matching errors. The UI can then use the Feedback
     * class to properly display the field errors.
     */
    public function registerFeedback()
    {
        Feedback::error($this->getErrors());
    }

    /**
     * Tries to retrieve the value of the specified field name. If the field is not registered, the default value is
     * then returned.
     *
     * @param string $field
     * @param mixed $defaultValue
     * @return mixed|null
     */
    public function getValue(string $field, $defaultValue = null)
    {
        if (isset($this->fields[$field])) {
            return $this->fields[$field]->getValue();
        }
        return $defaultValue;
    }

    /**
     * Retrieves all form fields in a simple associative array with the keys being the submitted field names and the
     * value being the submitted raw value (e.g. ['test' => 3]).
     *
     * @return array
     */
    public function getFields(): array
    {
        $fields = [];
        foreach ($this->fields as $field) {
            $fields[$field->getName()] = $field->getValue();
        }
        return $fields;
    }

    /**
     * Verifies if a specific field is currently registered in the submitted form.
     *
     * @param string $field
     * @return bool
     */
    public function isRegistered(string $field): bool
    {
        return isset($this->fields[$field]);
    }

    /**
     * @deprecated Should now be done directly on the field which ensure better flexibility.
     * @return bool
     */
    public function isOptionalOnEmpty(): bool
    {
        return $this->optionalOnEmpty;
    }

    /**
     * @deprecated Should now be done directly on the field which ensure better flexibility.
     * @param bool $emptyIsOptional
     */
    public function setOptionalOnEmpty(bool $emptyIsOptional)
    {
        $this->optionalOnEmpty = $emptyIsOptional;
    }

    /**
     * Returns an anonymous object with the fields as properties.
     *
     * @return stdClass
     */
    public function buildObject(): stdClass
    {
        return (object) $this->getFields();
    }
}
