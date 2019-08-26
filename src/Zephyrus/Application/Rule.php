<?php namespace Zephyrus\Application;

use Zephyrus\Application\Rules\BaseRules;
use Zephyrus\Application\Rules\SpecializedRules;
use Zephyrus\Application\Rules\StringRules;
use Zephyrus\Application\Rules\TimeRules;

class Rule
{
    /**
     * @var mixed
     */
    private $validation;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * Includes all rules defined as trait classes
     */
    use BaseRules;
    use SpecializedRules;
    use StringRules;
    use TimeRules;

    public function __construct($validation, string $errorMessage = "")
    {
        $this->validation = $validation;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Determines if the specified value matched the defined rule validation.
     *
     * @param mixed $value
     * @param array $fields
     * @return bool
     */
    public function isValid($value, array $fields = []): bool
    {
        $callback = new Callback($this->validation);
        $arguments = $this->getFunctionArguments($callback->getReflection(), $value, $fields);
        return $callback->executeArray($arguments);
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param \ReflectionFunctionAbstract $reflection
     * @param string $field
     * @return array
     */
    private function getFunctionArguments(\ReflectionFunctionAbstract $reflection, $value, $fields)
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
