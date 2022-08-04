<?php namespace Zephyrus\Application;

use RuntimeException;

class Configuration
{
    public const CONFIGURATION_PATH = ROOT_DIR . '/config.ini';

    private static ?ConfigurationFile $configurationFile = null;

    /**
     * Reads all available configurations.
     *
     * @return array
     */
    public static function getConfigurations(): array
    {
        if (is_null(self::$configurationFile)) {
            self::initializeConfigurations();
        }
        return self::$configurationFile->read();
    }

    public static function getFile(): ?ConfigurationFile
    {
        return self::$configurationFile;
    }

    public static function set(?array $configurations)
    {
        self::$configurationFile = null;
        if (!is_null($configurations)) {
            self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
            self::$configurationFile->write($configurations);
        }
    }

    public static function getApplicationConfiguration(?string $property = null, $defaultValue = null)
    {
        return self::getConfiguration('application', $property, $defaultValue);
    }

    public static function getLocaleConfiguration(?string $property = null, $defaultValue = null)
    {
        return self::getConfiguration('locale', $property, $defaultValue);
    }

    public static function getDatabaseConfiguration(?string $property = null, $defaultValue = null)
    {
        return self::getConfiguration('database', $property, $defaultValue);
    }

    public static function getSessionConfiguration(?string $property = null, $defaultValue = null)
    {
        return self::getConfiguration('session', $property, $defaultValue);
    }

    /**
     * Retrieves the configurations of the given section if no property has been set, otherwise, tries to read the
     * specified property within the given section. If it fails, the default value is returned.
     *
     * @param string $section
     * @param string|null $property
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public static function getConfiguration(string $section, ?string $property = null, mixed $defaultValue = null)
    {
        if (is_null(self::$configurationFile)) {
            self::initializeConfigurations();
        }
        return self::$configurationFile->read($section, $property, $defaultValue);
    }

    /**
     * Parse the .ini configuration file (/config.ini) into a PHP associative array including sections. Throws an
     * exception if file is not accessible.
     */
    private static function initializeConfigurations()
    {
        if (!is_readable(self::CONFIGURATION_PATH)) {
            throw new RuntimeException("Cannot parse configurations file (config.ini)");
        }
        self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
    }
}
