<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;
use Zephyrus\Utilities\Validations\ValidationCallback;

trait TimeRules
{
    public static function date(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::DATE_ISO, $errorMessage);
    }

    public static function time12Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isTime12Hours($data, $includeSeconds);
        }, $errorMessage);
    }

    public static function time24Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isTime24Hours($data, $includeSeconds);
        }, $errorMessage);
    }

    public static function dateTime12Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isDateTime12Hours($data, $includeSeconds);
        }, $errorMessage);
    }

    public static function dateTime24Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isDateTime24Hours($data, $includeSeconds);
        }, $errorMessage);
    }

    public static function dateBefore(string $referenceDate, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($referenceDate) {
            return Validation::isDateBefore($data, $referenceDate);
        }, $errorMessage);
    }

    public static function dateAfter(string $referenceDate, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($referenceDate) {
            return Validation::isDateAfter($data, $referenceDate);
        }, $errorMessage);
    }

    public static function dateBetween(string $referenceDateBegin, string $referenceDateEnd, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($referenceDateBegin, $referenceDateEnd) {
            return Validation::isDateBetween($data, $referenceDateBegin, $referenceDateEnd);
        }, $errorMessage);
    }
}
