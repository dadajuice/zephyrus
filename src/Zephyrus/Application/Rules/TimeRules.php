<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;

trait TimeRules
{
    public static function date(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isDate'], $errorMessage, 'date');
    }

    public static function time12Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isTime12Hours($data, $includeSeconds);
        }, $errorMessage, 'time12Hours');
    }

    public static function time24Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isTime24Hours($data, $includeSeconds);
        }, $errorMessage, 'time24Hours');
    }

    public static function dateTime12Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isDateTime12Hours($data, $includeSeconds);
        }, $errorMessage, 'dateTime12Hours');
    }

    public static function dateTime24Hours(string $errorMessage = "", bool $includeSeconds = false): Rule
    {
        return new Rule(function ($data) use ($includeSeconds) {
            return Validation::isDateTime24Hours($data, $includeSeconds);
        }, $errorMessage, 'dateTime24Hours');
    }

    public static function dateBefore(string $referenceDate, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($referenceDate) {
            return Validation::isDateBefore($data, $referenceDate);
        }, $errorMessage, 'dateBefore');
    }

    public static function dateAfter(string $referenceDate, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($referenceDate) {
            return Validation::isDateAfter($data, $referenceDate);
        }, $errorMessage, 'dateAfter');
    }

    public static function dateBetween(string $referenceDateBegin, string $referenceDateEnd, string $errorMessage = ""): Rule
    {
        return new Rule(function ($data) use ($referenceDateBegin, $referenceDateEnd) {
            return Validation::isDateBetween($data, $referenceDateBegin, $referenceDateEnd);
        }, $errorMessage, 'dateBetween');
    }
}
