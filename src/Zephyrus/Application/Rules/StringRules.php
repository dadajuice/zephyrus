<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validations\ValidationCallback;

trait StringRules
{
    public static function notEmpty(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::NOT_EMPTY, $errorMessage);
    }

    public static function name(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::NAME, $errorMessage);
    }

    public static function passwordCompliant(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::PASSWORD_COMPLIANT, $errorMessage);
    }

    public static function email(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::EMAIL, $errorMessage);
    }

    public static function alpha(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::ALPHA, $errorMessage);
    }

    public static function alphanumeric(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::ALPHANUMERIC, $errorMessage);
    }
}
