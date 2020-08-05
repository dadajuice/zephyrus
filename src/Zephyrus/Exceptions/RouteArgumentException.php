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
    private $errorMessage;

    public function __construct(string $argumentName, $value, string $errorMessage)
    {
        parent::__construct("The route argument {{$argumentName}} with value {{$value}} did not comply with defined rule and returned the following message : $errorMessage");
        $this->errorMessage = $errorMessage;
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
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }


}
