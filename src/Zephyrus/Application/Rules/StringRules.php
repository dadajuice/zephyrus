<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait StringRules
{
    public static function notEmpty(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isNotEmpty'], $errorMessage);
    }

    public static function name(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isName'], $errorMessage, 'name');
    }

    public static function passwordCompliant(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isPasswordCompliant'], $errorMessage);
    }

    public static function email(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isEmail'], $errorMessage);
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

    public static function variable(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isVariable'], $errorMessage);
    }
}
