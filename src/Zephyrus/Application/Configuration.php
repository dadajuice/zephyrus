<?php namespace Zephyrus\Application;

use RuntimeException;

class Configuration
{
    public const CONFIGURATION_PATH = ROOT_DIR . '/config.yml';
    private static ?ConfigurationFile $configurationFile = null;

    /**
     * Retrieves the configurations of the given property.
     *
     * @param string|null $property
     * @param mixed|null $defaultValue
     * @return mixed
     */
    public static function read(?string $property = null, mixed $defaultValue = null): mixed
    {
        if (is_null(self::$configurationFile)) {
            self::initializeConfigurations();
        }
        return self::$configurationFile->read($property, $defaultValue);
    }

    public static function getFile(): ?ConfigurationFile
    {
        return self::$configurationFile;
    }

    public static function write(?array $configurations): void
    {
        self::$configurationFile = null;
        if (!is_null($configurations)) {
            self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
            self::$configurationFile->write($configurations);
        }
    }

    public static function getApplication(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('application');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getLocale(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('locale');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getDatabase(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('database');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getSession(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('session');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    public static function getSecurity(?string $property = null, mixed $defaultValue = null): mixed
    {
        $configs = self::read('security');
        return ($property) ? $configs[$property] ?? $defaultValue : $configs;
    }

    /**
     * Parse the yml configuration file (/config.yml) into a PHP associative array including sections. Throws an
     * exception if file is not accessible.
     */
    private static function initializeConfigurations(): void
    {
        if (!is_readable(self::CONFIGURATION_PATH)) {
            throw new RuntimeException("Cannot parse configurations file [" . self::CONFIGURATION_PATH . "]");
        }
        self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
    }
}
