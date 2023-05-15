<?php namespace Zephyrus\Application;

use ReflectionFunctionAbstract;
use stdClass;
use Zephyrus\Application\Rules\BaseRules;
use Zephyrus\Application\Rules\FileRules;
use Zephyrus\Application\Rules\IterationRules;
use Zephyrus\Application\Rules\SpecializedRules;
use Zephyrus\Application\Rules\StringRules;
use Zephyrus\Application\Rules\TimeRules;

class Rule
{
    /**
     * Validation callback.
     *
     * @var callable
     */
    private $validation;

    /**
     * Holds the error message template to use once the rule fails. Allows %s inside the message to sprintf the failed
     * value.
     *
     * @var string
     */
    private string $errorMessage;

    /**
     * Contain the triggered errors. Consists of an array of stdClasses with the message, the value submitted, the
     * pathing and the field name.
     *
     * @var stdClass[]
     */
    private array $errors = [];

    /**
     * Keeps a reference to the original field name. Useful for adequate pathing.
     *
     * @var string
     */
    private string $fieldName = "";

    /**
     * Determines if the current rule is an iterator based, meaning the sum of its errors must come from its nested
     * rules and ignore the first level.
     *
     * @var bool
     */
    private bool $iterator = false;

    /**
     * Determines the pathing from the field to the error. Used for nested rules or iteration based rules.
     *
     * @var string
     */
    private string $pathing = "";

    /**
     * Keeps the original submitted value. Will be set only during validation phase as the value is only known once the
     * callback is launched.
     *
     * @var mixed
     */
    private mixed $value = "";

    /**
     * Applies the rule name.
     *
     * @var string
     */
    private string $name;

    /*
     * Includes all rules defined as trait classes
     */
    use BaseRules;
    use IterationRules;
    use SpecializedRules;
    use StringRules;
    use TimeRules;
    use FileRules;

    public function __construct(?callable $validation = null, string $errorMessage = "", string $name = "")
    {
        $this->validation = $validation;
        $this->errorMessage = $errorMessage;
        $this->name = $name;
    }

    /**
     * Applies the validation callback as the rule to be executed.
     *
     * @param callable $validation
     */
    public function setValidationCallback(callable $validation): void
    {
        $this->validation = $validation;
    }

    /**
     * Applies the original field name associated with the rule.
     *
     * @param string $name
     */
    public function setFieldName(string $name): void
    {
        $this->fieldName = $name;
    }

    /**
     * Checks if the rule is iteration based (e.g. each, eachKey and nested).
     *
     * @return bool
     */
    public function isIterator(): bool
    {
        return $this->iterator;
    }

    /**
     * Applies a rule pathing after the validation if available (useful for iteration based rules).
     *
     * @param string $pathing
     */
    public function setPathing(string $pathing): void
    {
        $this->pathing = $pathing;
    }

    /**
     * Retrieves the rule pathing generated after the validation.
     *
     * @return string
     */
    public function getPathing(): string
    {
        return $this->pathing;
    }

    /**
     * Determines if the specified value matched the defined rule validation. Launches the associated validation
     * callback and checks if it returns true.
     *
     * @param mixed $value
     * @param array $fields
     * @return bool
     */
    public function isValid(mixed $value, array $fields = []): bool
    {
        $this->value = $value;
        $callback = new Callback($this->validation);
        $arguments = $this->getFunctionArguments($callback->getReflection(), $value, $fields);
        return $callback->executeArray($arguments);
    }

    /**
     * Retrieves the error message associated with the rule. Will inject the validated data value inside the message if
     * there is an %s present (or any valid sprintf identifier). Only one substitution is available.
     *
     * @return string
     */
    public function getErrorMessage(): string
    {
        return sprintf($this->errorMessage, $this->value);
    }

    /**
     * Defines the error message to trigger if the rule fails.
     *
     * @param string $errorMessage
     */
    public function setErrorMessage(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * Retrieves the complete error details. Includes field name, failed value, error message and complete pathing.
     *
     * @return stdClass[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Accumulates an error for the rule.
     *
     * @param Rule $fromRule
     */
    public function triggerError(Rule $fromRule): void
    {
        $pathing = $fromRule->getPathing();
        if (!empty($pathing) || is_numeric($pathing)) {
            $pathing = '.' . $pathing;
        }
        if ($fromRule->isIterator()) {
            // When the rule is an iterator (e.g. nested), it can be a recursive rule. For such case, we append all the
            // triggered errors from the rule into this one.
            foreach ($fromRule->getErrors() as $error) {
                $this->errors[] = (object) [
                    'message' => $error->message,
                    'field' => $this->fieldName,
                    'pathing' => $this->fieldName . '.' . $error->pathing,
                    'value' => $error->value,
                    'failed_rule' => $error->failed_rule
                ];
                $fromRule->errors = [];
            }
        } else {
            $this->errors[] = (object) [
                'message' => $fromRule->getErrorMessage(),
                'field' => $this->fieldName,
                'pathing' => $this->fieldName . $pathing,
                'value' => $fromRule->value,
                'failed_rule' => $fromRule->name
            ];
        }
    }

    /**
     * Retrieves the needed function arguments for the validation callback. Can be either one argument which is the data
     * value or two arguments which are the data value and the entire form fields.
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param mixed $value
     * @param array $fields
     * @return array
     */
    private function getFunctionArguments(ReflectionFunctionAbstract $reflection, mixed $value, array $fields): array
    {
        $arguments = [];
        if ($reflection->getNumberOfParameters() == 1) {
            $arguments[] = $value;
        } elseif ($reflection->getNumberOfParameters() == 2) {
            $arguments[] = $value;
            $arguments[] = $fields;
        }
        return $arguments;
    }
}
