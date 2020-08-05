<?php namespace Zephyrus\Exceptions;

class RouteArgumentException extends \Exception
{
    /**
     * @var string
     */
    private $argumentName;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @var string
     */
    private $ruleError;

    public function __construct(string $argumentName, $value, string $message)
    {
        parent::__construct($message);
        $this->ruleError = $message;
        $this->argumentName = $argumentName;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getArgumentName(): string
    {
        return $this->argumentName;
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
    public function getError(): string
    {
        return $this->ruleError;
    }


}
