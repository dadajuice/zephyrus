<?php namespace Zephyrus\Application;

class Configuration
{
    const CONFIGURATION_PATH = ROOT_DIR . '/config.ini';

    /**
     * @var ConfigurationFile
     */
    private static $configurationFile = null;

    public static function getConfigurations()
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

    public static function getApplicationConfiguration($property = null)
    {
        return self::getConfiguration('application', $property);
    }

    public static function getSecurityConfiguration($property = null)
    {
        return self::getConfiguration('security', $property);
    }

    public static function getDatabaseConfiguration($property = null)
    {
        return self::getConfiguration('database', $property);
    }

    public static function getSessionConfiguration($property = null)
    {
        return self::getConfiguration('session', $property);
    }

    /**
     * @param string $section
     * @param string $property
     * @return mixed
     */
    public static function getConfiguration(string $section, ?string $property = null, $defaultValue = null)
    {
        if (is_null(self::$configurationFile)) {
            self::initializeConfigurations();
        }
        return self::$configurationFile->read($section, $property, $defaultValue);
    }

    /**
     * Parse the .ini configuration file (/config.ini) into a PHP associative
     * array including sections. Throws exception if file is not accessible.
     */
    private static function initializeConfigurations()
    {
        if (!is_readable(self::CONFIGURATION_PATH)) {
            throw new \RuntimeException("Cannot parse configurations file (config.ini)");
        }
        self::$configurationFile = new ConfigurationFile(self::CONFIGURATION_PATH);
    }
}
