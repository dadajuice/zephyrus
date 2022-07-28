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
    public const SQLITE_INVALID_DATABASE = 906;
    public const TRANSACTION_COMMIT_FAILED = 907;
    public const TRANSACTION_ROLLBACK_FAILED = 908;

    public static function connectionFailed(string $errorMessage): self
    {
        return new self(sprintf(self::codeToMessage(self::CONNECTION_FAILED), $errorMessage), self::CONNECTION_FAILED);
    }

    public static function missingConfiguration(string $configurationKey): self
    {
        return new self(sprintf(self::codeToMessage(self::MISSING_CONFIGURATION), $configurationKey), self::MISSING_CONFIGURATION);
    }

    public static function invalidPortConfiguration(): self
    {
        return new self(self::codeToMessage(self::INVALID_PORT_CONFIGURATION), self::INVALID_PORT_CONFIGURATION);
    }

    public static function driverNotAvailable(string $dbms): self
    {
        $availableDrivers = implode(',', DatabaseSource::getAvailableDrivers());
        return new self(sprintf(self::codeToMessage(self::DRIVER_NOT_AVAILABLE), $dbms, $availableDrivers), self::DRIVER_NOT_AVAILABLE);
    }

    public static function driverNotSupported(string $dbms): self
    {
        $supportedDrivers = implode(',', DatabaseSource::getSupportedDrivers());
        return new self(sprintf(self::codeToMessage(self::DRIVER_NOT_SUPPORTED), $dbms, $supportedDrivers), self::DRIVER_NOT_SUPPORTED);
    }

    public static function sqliteInvalidDatabase(string $path): self
    {
        return new self(sprintf(self::codeToMessage(self::SQLITE_INVALID_DATABASE), $path), self::SQLITE_INVALID_DATABASE);
    }

    public static function transactionCommitFailed(string $message): self
    {
        return new self(sprintf(self::codeToMessage(self::TRANSACTION_COMMIT_FAILED), $message), self::TRANSACTION_COMMIT_FAILED);
    }

    public static function transactionRollbackFailed(string $message): self
    {
        return new self(sprintf(self::codeToMessage(self::TRANSACTION_COMMIT_FAILED), $message), self::TRANSACTION_ROLLBACK_FAILED);
    }

    private static function codeToMessage(int $code): string
    {
        return match ($code) {
            self::CONNECTION_FAILED => "Connection to database failed with message [%s].",
            self::DRIVER_NOT_AVAILABLE => "The configured database management system [%s] doesn't correspond to one of the available drivers [%s].",
            self::DRIVER_NOT_SUPPORTED => "The configured database management system [%s] is currently unsupported by Zephyrus. Use one of the supported drivers [%s].",
            self::MISSING_CONFIGURATION => "The configuration key [%s] is needed for database initialisation.",
            self::INVALID_PORT_CONFIGURATION => "The database port configuration property must be int when specified.",
            self::SQLITE_INVALID_DATABASE => "The specified SQLite database file [%s] doesn't exists or is not readable (use database name [:memory:] to use in memory database).",
            self::TRANSACTION_COMMIT_FAILED => "Couldn't commit SQL transaction with message [%s]. Are you sure a transaction has been started ?",
            self::TRANSACTION_ROLLBACK_FAILED => "Couldn't rollback SQL transaction with message [%s]. Are you sure a transaction has been started ?",
            default => "Unknown fatal database error [$code].",
        };
    }
}
