<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;
use Zephyrus\Utilities\Validations\ValidationCallback;

trait SpecializedRules
{
    public static function url(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::URL, $errorMessage);
    }

    public static function youtubeUrl(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::URL_YOUTUBE, $errorMessage);
    }

    public static function phone(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::PHONE, $errorMessage);
    }

    public static function zipCode(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::ZIP_CODE, $errorMessage);
    }

    public static function postalCode(string $errorMessage = ""): Rule
    {
        return new Rule(ValidationCallback::POSTAL_CODE, $errorMessage);
    }

    public static function IPv4(string $errorMessage = "", bool $includeReserved = true, bool $includePrivate = true): Rule
    {
        return new Rule(function ($data) use ($includeReserved, $includePrivate) {
            return Validation::isIPv4($data, $includeReserved, $includePrivate);
        }, $errorMessage);
    }

    public static function IPv6(string $errorMessage = "", bool $includeReserved = true, bool $includePrivate = true): Rule
    {
        return new Rule(function ($data) use ($includeReserved, $includePrivate) {
            return Validation::isIPv6($data, $includeReserved, $includePrivate);
        }, $errorMessage);
    }

    public static function ipAddress(string $errorMessage = "", bool $includeReserved = true, bool $includePrivate = true): Rule
    {
        return new Rule(function ($data) use ($includeReserved, $includePrivate) {
            return Validation::isIpAddress($data, $includeReserved, $includePrivate);
        }, $errorMessage);
    }
}