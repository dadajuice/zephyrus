<?php namespace Zephyrus\Application\Rules;

use Zephyrus\Application\Rule;
use Zephyrus\Utilities\Validation;
use Zephyrus\Utilities\Validations\ValidationCallback;

trait SpecializedRules
{
    public static function url(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isUrl'], $errorMessage);
    }

    public static function liveUrl(string $errorMessage = "", array $acceptedValidCodes = [200, 201, 202, 204, 301, 302]): Rule
    {
        return new Rule(function ($data) use ($acceptedValidCodes) {
            return Validation::isLiveUrl($data, $acceptedValidCodes);
        }, $errorMessage);
    }

    public static function youtubeUrl(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isYoutubeUrl'], $errorMessage);
    }

    public static function json(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isJson'], $errorMessage);
    }

    public static function xml(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isXml'], $errorMessage);
    }

    public static function phone(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isPhone'], $errorMessage);
    }

    public static function zipCode(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isZipCode'], $errorMessage);
    }

    public static function postalCode(string $errorMessage = ""): Rule
    {
        return new Rule(['Zephyrus\Utilities\Validation', 'isPostalCode'], $errorMessage);
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
