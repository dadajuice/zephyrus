<?php namespace Zephyrus\Application;

class ErrorHandler
{
    /**
     * @var mixed[] Contains exception class name as key and corresponding
     * callback as value.
     */
    private $registeredThrowableCallbacks = [];

    /**
     * @var mixed[] Contains error type as key and corresponding callback as
     * value.
     */
    private $registeredErrorCallbacks = [];

    /**
     * Initialize every handlers (error, fatal and exceptions).
     */
    public function __construct()
    {
        set_exception_handler([$this, 'exceptionHandler']);
        set_error_handler([$this, 'errorHandler']);
    }

    /**
     * Defines a callback to use when a notice error occurs (E_USER_NOTICE,
     * E_NOTICE, E_DEPRECATED, E_USER_DEPRECATED).
     *
     * @param callable $callback
     * @throws \Exception
     */
    public function notice(callable $callback)
    {
        $this->registerError(E_DEPRECATED, $callback);
        $this->registerError(E_USER_DEPRECATED, $callback);
        $this->registerError(E_NOTICE, $callback);
        $this->registerError(E_USER_NOTICE, $callback);
    }

    /**
     * Defines a callback to use when a warning occurs which includes system
     * warning and user defined (E_WARNING, E_USER_WARNING, E_CORE_WARNING,
     * E_COMPILE_WARNING).
     *
     * @param callable $callback
     * @throws \Exception
     */
    public function warning(callable $callback)
    {
        $this->registerError(E_WARNING, $callback);
        $this->registerError(E_USER_WARNING, $callback);
        $this->registerError(E_CORE_WARNING, $callback);
        $this->registerError(E_COMPILE_WARNING, $callback);
    }

    /**
     * Defines a callback to use when a user defined error (E_USER_ERROR)
     * occurs.
     *
     * @param callable $callback
     * @throws \Exception
     */
    public function error(callable $callback)
    {
        $this->registerError(E_USER_ERROR, $callback);
    }

    /**
     * Defines a callback to use when a fatal error type occurs (E_ERROR_,
     * E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR).
     *
     * @param callable $callback
     * @throws \Exception
     */
    public function fatal(callable $callback)
    {
        $this->registerError(E_COMPILE_ERROR, $callback);
        $this->registerError(E_CORE_ERROR, $callback);
        $this->registerError(E_ERROR, $callback);
        $this->registerError(E_PARSE, $callback);
    }

    /**
     * Give a specific callback function to be used when a specific Exception
     * is thrown. This gives a great control over the program flow, specially
     * for generic application exceptions. The given callback must have only
     * one parameter hinted as an Exception subclass (or directly Exception if
     * the default Exception behavior must be overridden).
     *
     * @param callable $callback
     * @throws \Exception
     */
    public function exception(callable $callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $parameters = $reflection->getParameters();
        if (count($parameters) != 1) {
            throw new \InvalidArgumentException("Specified callback must only have one argument hinted as a 
                Throwable class");
        }
        $argumentClass = $parameters[0]->getClass();
        if (!$argumentClass->isSubclassOf('Throwable')) {
            throw new \InvalidArgumentException("Specified callback argument must be hinted child of a 
                Throwable class");
        }
        $this->registeredThrowableCallbacks[$argumentClass->getShortName()] = $callback;
    }

    /**
     * Register an error level with a specific user defined callback. The
     * specified callback can have up to 4 arguments, but they are not
     * required. First provided argument is the message, second is the file
     * path, third is the line number and the fourth is an error context.
     *
     * @param int $level
     * @param callable $callback
     * @throws \Exception
     */
    public function registerError($level, callable $callback)
    {
        $reflection = new \ReflectionFunction($callback);
        $parameters = $reflection->getParameters();
        if (count($parameters) > 4) {
            throw new \Exception("Specified callback cannot have more than 4 arguments (message, file, line, context)");
        }
        $this->registeredErrorCallbacks[$level] = $callback;
    }

    /**
     * When an exception is thrown, this method catches it and tries to find
     * the best user defined callback as a response. If there is no direct
     * callback associated, it will tries to find a definition within the
     * Exception class hierarchy. If nothing is found, the default behavior is
     * to die the script. Should not be called manually. Used as a registered
     * PHP handler.
     *
     * @param \Throwable $error
     * @throws \Throwable
     */
    public function exceptionHandler(\Throwable $error)
    {
        $reflection = new \ReflectionClass($error);
        $registeredException = $this->findRegisteredExceptions($reflection);
        if (!is_null($registeredException)) {
            $registeredException($error);
        }
    }

    /**
     * When an error, a notice or a warning is thrown, this method catches it
     * and tries to find the a user defined callback matching the PHP internal
     * error type. Will validate if the raised error type is included in the
     * error_reporting config. Should not be called manually. Used as a
     * registered PHP handler.
     *
     * @param int $type
     * @return bool
     * @throws \Exception
     */
    public function errorHandler($type, ...$args)
    {
        if (!(error_reporting() & $type)) {
            // This error code is not included in error_reporting
            return true;
        }
        if (array_key_exists($type, $this->registeredErrorCallbacks)) {
            $callback = $this->registeredErrorCallbacks[$type];
            $reflection = new \ReflectionFunction($callback);
            $reflection->invokeArgs($args);
            return true;
        }
        return false;
    }

    /**
     * @param \ReflectionClass $reflection
     * @return callable|null
     */
    private function findRegisteredExceptions(\ReflectionClass $reflection)
    {
        $exceptionClass = $reflection->getShortName();
        if (isset($this->registeredThrowableCallbacks[$exceptionClass])) {
            return $this->registeredThrowableCallbacks[$exceptionClass];
        }
        while ($parent = $reflection->getParentClass()) {
            if (isset($this->registeredThrowableCallbacks[$parent->getShortName()])) {
                return $this->registeredThrowableCallbacks[$parent->getShortName()];
            }
            $reflection = $parent;
        }
        return null;
    }
}
