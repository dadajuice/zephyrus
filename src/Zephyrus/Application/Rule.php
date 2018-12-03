<?php namespace Zephyrus\Application;

use Zephyrus\Utilities\Validator;

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

    public static function notEmpty(string $errorMessage = "")
    {
        return new self(Validator::NOT_EMPTY, $errorMessage);
    }

    public static function name(string $errorMessage = "")
    {
        return new self(Validator::NAME, $errorMessage);
    }

    public static function passwordCompliant(string $errorMessage = "")
    {
        return new self(Validator::PASSWORD_COMPLIANT, $errorMessage);
    }

    public static function decimal(string $errorMessage = "", $allowSigned = false)
    {
        return new self((!$allowSigned) ? Validator::DECIMAL : Validator::DECIMAL_SIGNED, $errorMessage);
    }

    public static function integer(string $errorMessage = "", $allowSigned = false)
    {
        return new self((!$allowSigned) ? Validator::INTEGER : Validator::INTEGER_SIGNED, $errorMessage);
    }

    public static function email(string $errorMessage = "")
    {
        return new self(Validator::EMAIL, $errorMessage);
    }

    public static function date(string $errorMessage = "")
    {
        return new self(Validator::DATE_ISO, $errorMessage);
    }

    public static function time12Hours(string $errorMessage = "")
    {
        return new self(Validator::TIME_12HOURS, $errorMessage);
    }

    public static function time24Hours(string $errorMessage = "")
    {
        return new self(Validator::TIME_24HOURS, $errorMessage);
    }

    public static function alpha(string $errorMessage = "")
    {
        return new self(Validator::ALPHA, $errorMessage);
    }

    public static function alphanumeric(string $errorMessage = "")
    {
        return new self(Validator::ALPHANUMERIC, $errorMessage);
    }

    public static function url(string $errorMessage = "")
    {
        return new self(Validator::URL, $errorMessage);
    }

    public static function phone(string $errorMessage = "")
    {
        return new self(Validator::PHONE, $errorMessage);
    }

    public static function zipCode(string $errorMessage = "")
    {
        return new self(Validator::ZIP_CODE, $errorMessage);
    }

    public static function postalCode(string $errorMessage = "")
    {
        return new self(Validator::POSTAL_CODE, $errorMessage);
    }

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
