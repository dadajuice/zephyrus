<?php namespace Zephyrus\Application;

use stdClass;

class FormField
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var stdClass[]
     */
    private $rules = [];

    /**
     * @var string[]
     */
    private $errorMessages = [];

    /**
     * @var bool
     */
    private $optionalOnEmpty = true;

    /**
     * @var bool
     */
    private $verifyAll = false;

    public function __construct(string $name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Adds a rule to be applied to the field when verify is called.
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
     * Instead of stopping at the first failed validation, it will proceed to validate all given rules and accumulate
     * errors. Useful to retrieve all errors on a field at once.
     */
    public function all()
    {
        $this->verifyAll = true;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }

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
