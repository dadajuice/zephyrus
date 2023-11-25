<?php namespace Zephyrus\Utilities;

use BadMethodCallException;
use Zephyrus\Utilities\Formatters\NumericFormatter;
use Zephyrus\Utilities\Formatters\SpecializedFormatter;
use Zephyrus\Utilities\Formatters\TimeFormatter;

class Formatter
{
    private static array $customFormatters = [];

    use NumericFormatter;
    use TimeFormatter;
    use SpecializedFormatter;

    public static function register(string $name, mixed $callback): void
    {
        self::$customFormatters[$name] = $callback;
    }

    public static function hasCustomFormatter(string $name): bool
    {
        return isset(self::$customFormatters[$name]);
    }

    public static function __callStatic($method, $parameters)
    {
        if (!self::hasCustomFormatter($method)) {
            throw new BadMethodCallException("Method {$method} does not exist.");
        }
        $customFormatter = self::$customFormatters[$method];
        return call_user_func_array($customFormatter, $parameters);
    }
}
