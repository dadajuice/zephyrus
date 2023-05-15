<?php namespace Zephyrus\Application;

class FormField
{
    /**
     * Submitted field name.
     *
     * @var string
     */
    private string $name;

    /**
     * Raw value as it was submitted.
     *
     * @var mixed
     */
    private mixed $value;

    /**
     * Internal list of all rules assigned to the field in programmatic order.
     *
     * @var Rule[]
     */
    private array $rules = [];

    /**
     * Associative array for all the registered error of the field with the key being the error pathing. Default pathing
     * is the field name.
     *
     * @var array
     */
    private array $errors = [];

    /**
     * Determines if empty values should be interpreted as NULL when using the getValue method.
     *
     * @var bool
     */
    private bool $nullable = false;

    /**
     * Determines if the rules apply when the value is empty.
     *
     * @var bool
     */
    private bool $optional = false;

    /**
     * Determines if all the rules should execute or bail on the first failure (default behavior).
     *
     * @var bool
     */
    private bool $verifyAll = false;

    /**
     * Determines if the field value should remain in the session to be displayed back on error. Default behavior is to
     * remove it.
     *
     * @var bool
     */
    private bool $keepOnError = false;

    /**
     * Class constructor to initialize a field with its name and value.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __construct(string $name, mixed $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Adds the given rules to the validation scheme of the field. Be mindful of the rule orders as they will be
     * verified in programmatic order, so if some rules are dependent on others, be sure to add those beforehand.
     *
     * @param Rule[] $rules
     * @return self
     */
    public function validate(array $rules): self
    {
        foreach ($rules as $rule) {
            $this->rules[] = $rule;
        }
        return $this;
    }

    /**
     * Verify the validation rules on the field and register errors if any arise. The $fields argument should contain
     * all the form data available because some rules may need to access other form fields. Returns true is all
     * validation have passed or false otherwise. Field is considered valid if it is flagged as optional and the
     * submitted value is considered empty (either null or empty string ['', ""]).
     *
     * @param array $fields
     * @return bool
     */
    public function verify(array $fields = []): bool
    {
        if ($this->optional && $this->isEmpty()) {
            return true;
        }
        foreach ($this->rules as $rule) {
            $rule->setFieldName($this->name);
            if (!$rule->isValid($this->value, $fields)) {
                if (!$rule->isIterator()) { // Do not consider iterator
                    $rule->triggerError($rule);
                }
                $this->addError($rule);
                if (!$this->keepOnError) {
                    Form::removeMemorizedValue($this->name);
                }
                if (!$this->verifyAll) {
                    return false;
                }
            }
        }
        return empty($this->errors);
    }

    /**
     * Instead of stopping at the first failed rule validation, it will proceed to validate all given rules and
     * accumulate errors. Useful to retrieve all errors on a field at once. Default behavior is to stop and return the
     * first encountered error on a field.
     *
     * @return self
     */
    public function all(): self
    {
        $this->verifyAll = true;
        return $this;
    }

    /**
     * The field value will return NULL in case of an empty raw value. Useful for easier database submission into null
     * fields. Note that zeros are not considered empty. Empty raw values are either empty string ['', ""] or null.
     *
     * @return self
     */
    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * Determines if the field is optional, meaning it will not execute rules on an empty value (string ['', ""] or
     * null). Zeros are considered not empty and thus will execute validations.
     *
     * @return self
     */
    public function optional(): self
    {
        $this->optional = true;
        return $this;
    }

    /**
     * Determines if the value should be keep in form even when there's an error.
     *
     * @return self
     */
    public function keep(): self
    {
        $this->keepOnError = true;
        return $this;
    }

    /**
     * Retrieves the given value of the field. If nullable is set and the raw value is empty (either null or empty
     * string ['', ""]), it will return null. Returns mixed because it could be a nested array, a number or an object
     * in the case of a json content-type submission.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->isEmpty() && $this->nullable ? null : $this->value;
    }

    /**
     * Retrieves the given name of the field. E.g. if an array has been submitted with the name "selections[]", this
     * name would be returned. Does not alter the submitted name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the complete error information including the pathing. Returns an associative array where the key is
     * the pathing and the value is an array of messages.
     *
     * @return array
     */
    public function getErrors(): array
    {
        $result = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $error) {
                if (!isset($result[$error->pathing])) {
                    $result[$error->pathing] = [];
                }
                $result[$error->pathing][] = $error->message;
            }
        }
        return $result;
    }

    /**
     * Retrieves the list of registered errors.
     *
     * @return string[]
     */
    public function getErrorMessages(): array
    {
        $messages = [];
        foreach ($this->errors as $errors) {
            foreach ($errors as $error) {
                $messages[] = $error->message;
            }
        }
        return $messages;
    }

    /**
     * Checks if there is at least one error registered for the field.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Helper method to determine if the given raw value is considered empty (either null or empty string ['', ""]), in
     * this case, zeros are considered not empty.
     *
     * @return bool
     */
    private function isEmpty(): bool
    {
        $data = $this->value;
        if (is_numeric($data)) {
            return false;
        }
        return empty(is_string($data) ? trim($data) : $data);
    }

    /**
     * Inserts the error message associated with the given rule. The internal error array key represents the pathing
     * useful for nested errors. If the field has nested errors the pathing could be "students.2.name" which means
     * students[2]['name'] has the error. By default, the pathing is simply the field name.
     *
     * @param Rule $rule
     */
    private function addError(Rule $rule): void
    {
        foreach ($rule->getErrors() as $error) {
            if (!isset($this->errors[$error->field])) {
                $this->errors[$error->field] = [];
            }
            $this->errors[$error->field][] = $error;
        }
    }
}
