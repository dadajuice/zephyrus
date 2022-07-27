<?php namespace Zephyrus\Exceptions;

use Exception;
use Zephyrus\Database\Core\DatabaseSource;

class FatalDatabaseException extends Exception
{
    public const CONNECTION_FAILED = 901;
    public const DRIVER_NOT_AVAILABLE = 902;
    public const DRIVER_NOT_SUPPORTED = 903;
    public const MISSING_CONFIGURATION = 904;
    public const INVALID_PORT_CONFIGURATION = 905;

    public static function connectionFailed(string $errorMessage): self
    {
        return new self(sprintf(self::codeToMessage(self::CONNECTION_FAILED), $errorMessage));
    }

    public static function missingConfiguration(string $configurationKey): self
    {
        return new self(sprintf(self::codeToMessage(self::MISSING_CONFIGURATION), $configurationKey));
    }

    public static function invalidPortConfiguration(): self
    {
        return new self(self::codeToMessage(self::INVALID_PORT_CONFIGURATION));
    }

    public static function driverNotAvailable(string $dbms): self
    {
        $availableDrivers = implode(',', DatabaseSource::getAvailableDrivers());
        return new self(sprintf(self::codeToMessage(self::DRIVER_NOT_AVAILABLE), $dbms, $availableDrivers));
    }

    public static function driverNotSupported(string $dbms): self
    {
        $supportedDrivers = implode(',', DatabaseSource::getSupportedDrivers());
        return new self(sprintf(self::codeToMessage(self::DRIVER_NOT_SUPPORTED), $dbms, $supportedDrivers));
    }

    private static function codeToMessage(int $code): string
    {
        return match ($code) {
            self::CONNECTION_FAILED => "Connection to database failed with message [%s].",
            self::DRIVER_NOT_AVAILABLE => "The configured database management system [%s] doesn't correspond to one of the available drivers [%s].",
            self::DRIVER_NOT_SUPPORTED => "The configured database management system [%s] is currently unsupported by Zephyrus. Use one of the supported drivers [%s].",
            self::MISSING_CONFIGURATION => "The configuration key [%s] is needed for database initialisation.",
            self::INVALID_PORT_CONFIGURATION => "The database port configuration property must be int when specified.",
            default => "Unknown fatal database error [$code].",
        };
    }
}
