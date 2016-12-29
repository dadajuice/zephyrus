<?php namespace Zephyrus\Application;

use Zephyrus\Network\Request;

class Form
{
    const TRIGGER_ALWAYS = 0;
    const TRIGGER_NO_ERROR = 1;
    const TRIGGER_FIELD_NO_ERROR = 2;

    /**
     * @var array
     */
    private $fields = [];

    /**
     * @var array
     */
    private $errors = [];

    /**
     * @var array
     */
    private $validations = [];

    /**
     * Reads a memorized value for a given fieldId. If value has not been set the
     * specified default value is assigned (empty if not set). Excellent to set
     * remembered data in forms.
     *
     * @param string $fieldId
     * @param string $defaultValue
     * @return string
     */
    public static function readMemorizedValue($fieldId, $defaultValue = "")
    {
        return (isset($_SESSION['_FIELDS'][$fieldId])) ? $_SESSION['_FIELDS'][$fieldId] : $defaultValue;
    }

    /**
     * Memorizes the specified value for the given fieldId. Allows to be read by
     * the readMemorizedValue() function afterward.
     *
     * @param string $fieldId
     * @param string $value
     */
    public static function memorizeValue($fieldId, $value)
    {
        if (!isset($_SESSION['_FIELDS'])) {
            $_SESSION['_FIELDS'] = [];
        }
        $_SESSION['_FIELDS'][$fieldId] = $value;
    }

    /**
     * Removes the specified fieldId from memory or clears the entire memorized
     * fields if not set.
     *
     * @param string $fieldId
     */
    public static function removeMemorizedValue($fieldId = null)
    {
        if (isset($_SESSION['_FIELDS'])) {
            if (is_null($fieldId)) {
                $_SESSION['_FIELDS'] = null;
                unset($_SESSION['_FIELDS']);
            } else {
                unset($_SESSION['_FIELDS'][$fieldId]);
            }
        }
    }

    public function __construct($initializeFromRequest = true)
    {
        if ($initializeFromRequest) {
            $parameters = Request::getParameters();
            foreach ($parameters as $field => $value) {
                $this->addField($field, $value);
            }
        }
    }

    /**
     * @return bool
     */
    public function verify()
    {
        foreach ($this->validations as $field => $validations) {
            $this->verifyAllRegisteredFields($field, $validations);
        }
        return empty($this->errors);
    }

    public function addRule($field, $validation, $errorMessage, $trigger = self::TRIGGER_ALWAYS)
    {
        if (!array_key_exists($field, $this->fields)) {
            throw new \Exception("Specified field [$field] has not been registered in this request");
        }

        $callback = null;
        if (is_callable($validation)) {
            //TODO: validate callback
            $callback = $validation;
        }

        $this->validations[$field][] = [
            'callback' => $callback,
            'message' => $errorMessage,
            'trigger' => $trigger
        ];
    }

    /**
     * @param string[] $parameterNames
     */
    public function addFields(array $parameterNames)
    {
        foreach ($parameterNames as $parameter) {
            $this->addField($parameter);
        }
    }

    /**
     * @param string $parameterName
     * @param mixed $value
     */
    public function addField($parameterName, $value = null)
    {
        $this->fields[$parameterName] = is_null($value) ? Request::getParameter($parameterName) : $value;
    }

    /**
     * @param string $field
     * @param string $message
     */
    public function addError($field, $message)
    {
        $this->errors[$field][] = $message;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return string[]
     */
    public function getErrorMessages()
    {
        $results = [];
        foreach ($this->errors as $fields => $errors) {
            foreach ($errors as $message) {
                $results[] = $message;
            }
        }
        return $results;
    }

    public function getValue($field)
    {
        return $this->fields[$field];
    }

    public function getValues()
    {
        return $this->fields;
    }

    /**
     * @param string $field
     * @param array $validations
     * @throws \Exception
     */
    private function verifyAllRegisteredFields($field, $validations)
    {
        foreach ($validations as $validation) {
            if ($validation['trigger'] > self::TRIGGER_ALWAYS) {
                if ($validation['trigger'] == self::TRIGGER_NO_ERROR) {
                    if (!empty($this->errors)) {
                        continue;
                    }
                } elseif ($validation['trigger'] == self::TRIGGER_FIELD_NO_ERROR) {
                    if (isset($this->errors[$field])) {
                        continue;
                    }
                }
            }
            if (!$this->executeRule($field, $validation['callback'])) {
                $this->errors[$field][] = $validation['message'];
                self::removeMemorizedValue($field);
            } else {
                self::memorizeValue($field, $this->fields[$field]);
            }
        }
    }

    /**
     * @param $field
     * @param $callback
     * @return mixed
     */
    private function executeRule($field, $callback)
    {
        $isObjectMethod = is_array($callback);
        if ($isObjectMethod) {
            return $this->executeMethod($field, $callback);
        } else {
            return $this->executeFunction($field, $callback);
        }
    }

    /**
     * Execute the specified callback function
     *
     * @param string $field
     * @param callable $callback
     * @return mixed
     */
    private function executeFunction($field, $callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $arguments = $this->getFunctionArguments($reflection, $field);
        return $reflection->invokeArgs($arguments);
    }

    /**
     * Execute the specified callback object method. Works with static calls
     * or instance method.
     *
     * @param string $field
     * @param callable $callback
     * @return mixed
     */
    private function executeMethod($field, $callback)
    {
        $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        $arguments = $this->getFunctionArguments($reflection, $field);
        if ($reflection->isStatic()) {
            return $reflection->invokeArgs(null, $arguments);
        } elseif (is_object($callback[0])) {
            return $reflection->invokeArgs($callback[0], $arguments);
        } else {
            $instance = new $callback[0]();
            return $reflection->invokeArgs($instance, $arguments);
        }
    }

    /**
     * Retrieves the specified function arguments.
     *
     * @param \ReflectionFunctionAbstract $reflection
     * @param string $field
     * @return array
     */
    private function getFunctionArguments(\ReflectionFunctionAbstract $reflection, $field)
    {
        $arguments = [];
        if ($reflection->getNumberOfParameters() == 1) {
            $arguments[] = $this->fields[$field];
        } elseif ($reflection->getNumberOfParameters() == 2) {
            $arguments[] = $this->fields[$field];
            $arguments[] = $this->fields;
        }
        return $arguments;
    }
}