<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validations\ValidationCallback;

trait TimeRules
{
    public static function date(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::DATE_ISO, $errorMessage);
    }

    public static function time12Hours(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::TIME_12HOURS, $errorMessage);
    }

    public static function time24Hours(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::TIME_24HOURS, $errorMessage);
    }

    public static function dateTime12Hours(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::DATE_TIME_12HOURS, $errorMessage);
    }

    public static function dateTime24Hours(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::DATE_TIME_24HOURS, $errorMessage);
    }
}
