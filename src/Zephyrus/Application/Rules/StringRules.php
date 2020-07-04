<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;
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

    public static function alpha(string $errorMessage = "", bool $considerAccentedChar = true): Rule
    {
        return new Rule(function ($data) use ($considerAccentedChar) {
            return Validation::isAlpha($data, $considerAccentedChar);
        }, $errorMessage);
    }

    public static function alphanumeric(string $errorMessage = "", bool $considerAccentedChar = true): Rule
    {
        return new Rule(function ($data) use ($considerAccentedChar) {
            return Validation::isAlphanumeric($data, $considerAccentedChar);
        }, $errorMessage);
    }

    public static function minLength(int $minLength, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($minLength) {
            return Validation::isMinLength($data, $minLength);
        }, $errorMessage);
    }

    public static function maxLength(int $maxLength, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($maxLength) {
            return Validation::isMaxLength($data, $maxLength);
        }, $errorMessage);
    }
}
