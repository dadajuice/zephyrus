<?php namespace Zephyrus\Application;

use ReflectionClass;
use ReflectionException;

class Bootstrap
{
    public static function getHelperFunctionsPath(): string
    {
        return realpath(__DIR__ . '/../functions.php');
    }
}
