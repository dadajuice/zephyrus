<?php namespace Zephyrus\Application;

class Callback
{
    private $callback;
    private $reflection;

    public function __construct($callback)
    {
        $this->callback = $callback;
        $this->prepareReflection();
    }

    /**
     * Execute the specified callback function or method.
     *
     * @param array $args
     * @return mixed
     */
    public function execute(...$args)
    {
        return $this->executeArray($args);
    }

    /**
     * @param array $args
     * @return mixed
     */
    public function executeArray(array $args)
    {
        return (is_array($this->callback))
            ? $this->executeMethod($args)
            : $this->executeFunction($args);
    }

    /**
     * @return \ReflectionFunction|\ReflectionMethod
     */
    public function getReflection()
    {
        return $this->reflection;
    }

    private function prepareReflection()
    {
        $this->reflection = (is_array($this->callback))
            ? new \ReflectionMethod($this->callback[0], $this->callback[1])
            : new \ReflectionFunction($this->callback);
    }

    /**
     * Execute the specified callback function
     *
     * @param array $arguments
     * @return mixed
     */
    private function executeFunction(array $arguments)
    {
        return $this->reflection->invokeArgs($arguments);
    }

    /**
     * Execute the specified callback object method. Works with static calls
     * or instance method.
     *
     * @param array $arguments
     * @return mixed
     */
    private function executeMethod(array $arguments)
    {
        if ($this->reflection->isStatic()) {
            return $this->reflection->invokeArgs(null, $arguments);
        } elseif (is_object($this->callback[0])) {
            return $this->reflection->invokeArgs($this->callback[0], $arguments);
        }
        $instance = new $this->callback[0]();
        return $this->reflection->invokeArgs($instance, $arguments);
    }
}