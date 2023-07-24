<?php namespace Zephyrus\Application;

use stdClass;

class Form
{
    private const SESSION_KEY = '__ZF__FIELDS';

    /**
     * List of all the submitted fields.
     *
     * @var FormField[]
     */
    private array $fields = [];

    /**
     * Contains all the triggered errors. Key are the name (with pathing if available) and value are an array or error
     * messages.
     *
     * @var array
     */
    private array $errors = [];

    /**
     * Reads a memorized value for a given fieldId. If value has not been set the specified default value is
     * assigned (empty if not set). Used mostly to set remembered data in forms.
     *
     * @param string $fieldId
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function readMemorizedValue(string $fieldId, mixed $defaultValue = ""): mixed
    {
        return (isset($_SESSION[self::SESSION_KEY][$fieldId]))
            ? $_SESSION[self::SESSION_KEY][$fieldId]
            : $defaultValue;
    }

    /**
     * Memorizes the specified value for the given fieldId. Allows to be read by the readMemorizedValue() function
     * afterward. Works with submitted array type values.
     *
     * @param string $fieldId
     * @param mixed $value
     */
    public static function memorizeValue(string $fieldId, mixed $value): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $fieldId = str_replace("[]", "", $fieldId);
                self::memorizeValue("$fieldId" . "[" . $key . "]", $val);
            }
        } else {
            $_SESSION[self::SESSION_KEY][$fieldId] = $value;
        }
    }

    /**
     * Removes the specified fieldId from memory or clears the entire memorized fields if not set.
     *
     * @param string|null $fieldId
     */
    public static function removeMemorizedValue(?string $fieldId = null): void
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

    /**
     * Inserts a list of fields into the form data. formData is an associative array where the keys are the field name
     * and the value the corresponding values.
     *
     * @param array $formData
     */
    public function __construct(array $formData = [])
    {
        $this->addFields($formData);
    }

    /**
     * Retrieves a field from the form. If the field doesn't exist, a corresponding field will be added to the form data
     * with a NULL value. Useful to validate a required checkbox for example. If the rules are set, it will be applied
     * to the field before returning it.
     *
     * @param string $name
     * @param Rule[] $rules
     * @return FormField
     */
    public function field(string $name, array $rules = []): FormField
    {
        if (!isset($this->fields[$name])) {
            $this->addField($name, null);
        }
        return empty($rules) ? $this->fields[$name] : $this->fields[$name]->validate($rules);
    }

    /**
     * Validates each submitted fields with their configured rules. Accumulates errors.
     *
     * @return bool
     */
    public function verify(): bool
    {
        foreach ($this->fields as $field) {
            if (!$field->verify($this->getFields())) {
                $this->errors = $this->errors + $field->getErrors();
            }
        }
        return empty($this->errors);
    }

    /**
     * Inserts a list of fields into the form data. formData is an associative array where the keys are the field name
     * and the value the corresponding values.
     *
     * @param array $formData
     */
    public function addFields(array $formData): void
    {
        foreach ($formData as $parameterName => $value) {
            $this->addField($parameterName, $value);
        }
    }

    /**
     * Inserts a field into the form data.
     *
     * @param string $name
     * @param mixed $value
     */
    public function addField(string $name, mixed $value): void
    {
        $this->fields[$name] = new FormField($name, $value);
        self::memorizeValue($name, $value);
    }

    /**
     * Removes the specified field from the form data.
     *
     * @param string $name
     */
    public function removeField(string $name): void
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
            self::removeMemorizedValue($name);
        }
    }

    /**
     * Manually adds an error to the form for the specified field.
     *
     * @param string $name
     * @param string $message
     */
    public function addError(string $name, string $message): void
    {
        if (!isset($this->errors[$name])) {
            $this->errors[$name] = [];
        }
        $this->errors[$name][] = $message;
    }

    /**
     * Verifies if the form has error for a specified field or the entire form (if no field is provided).
     *
     * @param string|null $name
     * @return bool
     */
    public function hasError(?string $name = null): bool
    {
        return (is_null($name))
            ? !empty($this->errors)
            : isset($this->errors[$name]);
    }

    /**
     * Retrieves all the registered errors of the form in an associative array which the keys are the field name and the
     * values are an array of error messages. The keys may contain pathing for nested object errors.
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
            foreach ($errors as $errorMessage) {
                $messages[] = $errorMessage;
            }
        }
        return $messages;
    }

    /**
     * Automatically prepares the feedbacks for the form fields with matching errors. The UI can then use the Feedback
     * class to properly display the field errors.
     */
    public function registerFeedback(): void
    {
        Feedback::register($this->getErrors());
    }

    /**
     * Automatically prepares the flash errors from the accumulated error messages.
     */
    public function registerFlash(): void
    {
        Flash::error($this->getErrorMessages());
    }

    /**
     * Tries to retrieve the value of the specified field name. If the field is not registered, the default value is
     * then returned.
     *
     * @param string $name
     * @param mixed $defaultValue
     * @return mixed|null
     */
    public function getValue(string $name, mixed $defaultValue = null): mixed
    {
        return isset($this->fields[$name]) ? $this->fields[$name]->getValue() : $defaultValue;
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
     * Returns an anonymous object with the fields as properties.
     *
     * @return stdClass
     */
    public function buildObject(): stdClass
    {
        $objectProperties = [];
        foreach ($this->getFields() as $field => $value) {
            $objectProperties[rtrim($field, "[]")] = $value;
        }
        return (object) $objectProperties;
    }
}
