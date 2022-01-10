<?php namespace Zephyrus\Application;

use stdClass;

class FormField
{
    /**
     * Holds the field name as registered when the form has been submitted. E.g. if an array has been submitted with the
     * name "selections[]", this name would be registered.
     *
     * @var string
     */
    private string $name;

    /**
     * Holds the given field value when the form has been submitted.
     *
     * @var mixed
     */
    private mixed $value;

    /**
     * Internal list of all rules assigned to the field in programmatic order. Meaning the order the addRule method is
     * called is important as the rules will be executed in such order.
     *
     * @var stdClass[]
     */
    private array $rules = [];

    /**
     * List of error messages currently registered to the field. This list is filled when the verify method is called
     * and one or many rules have failed. Then the attached message to the rule will be registered in this list.
     *
     * @var string[]
     */
    private array $errorMessages = [];

    /**
     * Dictates how to handle a field with an empty value with an optional rule. By default, an optional rule on an
     * empty value is considered optional. Can be changed if for some reason a specific field is not considered optional
     * when empty.
     *
     * @var bool
     */
    private bool $optionalOnEmpty = true;

    /**
     * Dictates if the rule verification should stop when an error is encountered or if all validation should be
     * executed nonetheless. By default, the validation process ends when an error is encountered because traditionally
     * all rules affected to a field are somewhat dependant (e.g. ->validate(Rule::notEmpty())->validate(Rule::name()))
     * and thus would make the resulting errors redondant.
     *
     * @var bool
     */
    private bool $verifyAll = false;

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
     * Registers a rule or a group of rules to be applied to the field. The optional argument allows the rule to be
     * skipped if the value is undefined or empty (depending on the optionalOnEmpty). Rules are verified only when the
     * verify method is called.
     *
     * @param Rule|array $rule
     * @param bool $optional
     * @return FormField
     */
    public function validate(Rule|array $rule, bool $optional = false): FormField
    {
        if (is_array($rule)) {
            foreach ($rule as $item) {
                $this->addRule($item, $optional);
            }
        } else {
            $this->addRule($rule, $optional);
        }
        return $this;
    }

    /**
     * Instead of stopping at the first failed rule validation, it will proceed to validate all given rules and
     * accumulate errors. Useful to retrieve all errors on a field at once. Default behavior is to stop and return the
     * first encountered error on a field.
     */
    public function all()
    {
        $this->verifyAll = true;
    }

    /**
     * Retrieves the given value of the field.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Retrieves the given name of the field.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the list of registered errors.
     *
     * @return string[]
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

    /**
     * Verify the affected validation rules on the field. The $fields argument should contain all the form data
     * available because some rules may need to access other form fields. Returns true is all validation have passed or
     * false otherwise. If it returns false, the errors can be read with getErrorMessages().
     *
     * @param array $fields
     * @return bool
     */
    public function verify(array $fields = []): bool
    {
        foreach ($this->rules as $validation) {
            if ($this->isRuleTriggered($validation) && !$validation->rule->isValid($this->value, $fields)) {
                $this->errorMessages[] = $validation->rule->getErrorMessage();
                if (!$this->verifyAll) {
                    return false;
                }
            }
        }
        return empty($this->errorMessages);
    }

    /**
     * @return bool
     */
    public function isOptionalOnEmpty(): bool
    {
        return $this->optionalOnEmpty;
    }

    /**
     * @param bool $emptyIsOptional
     */
    public function setOptionalOnEmpty(bool $emptyIsOptional)
    {
        $this->optionalOnEmpty = $emptyIsOptional;
    }

    private function isRuleTriggered(stdClass $validation): bool
    {
        if (!$validation->optional) {
            return true;
        }
        if (is_null($this->value)
            || ($this->optionalOnEmpty && empty($this->value))) {
            return false;
        }
        return true;
    }

    private function addRule(Rule $rule, bool $optional)
    {
        $validation = new stdClass();
        $validation->rule = $rule;
        $validation->optional = $optional;
        $this->rules[] = $validation;
    }
}
