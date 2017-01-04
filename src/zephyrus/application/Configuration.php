<?php namespace Zephyrus\Application;

class Configuration
{
    /**
     * @var mixed[] Application configurations in associative array form
     */
    private static $config = null;

    final public static function getConfigurations()
    {
        if (is_null(self::$config)) {
            self::initializeConfigurations();
        }
        return self::$config;
    }

    public static function getApplicationConfiguration($config = null)
    {
        return self::getConfiguration('application', $config);
    }

    public static function getSecurityConfiguration($config = null)
    {
        return self::getConfiguration('security', $config);
    }

    public static function getDatabaseConfiguration($config = null)
    {
        return self::getConfiguration('database', $config);
    }

    public static function getSessionConfiguration($config = null)
    {
        return self::getConfiguration('session', $config);
    }

    /**
     * @param string $section
     * @param string $config
     * @return mixed
     * @throws \Exception
     */
    public static function getConfiguration($section, $config = null)
    {
        if (is_null(self::$config)) {
            self::initializeConfigurations();
        }

        self::validateRequiredSection($section);
        if (!is_null($config)) {
            self::validateRequiredConfigurationField($section, $config);
            return self::$config[$section][$config];
        }
        return self::$config[$section];
    }

    /**
     * Parse the .ini configuration file (/config.ini) into a PHP associative
     * array including sections. Throws exception if file is not accessible.
     *
     * @throws \Exception
     */
    private static function initializeConfigurations()
    {
        if (is_readable('../config.ini')) {
            self::$config = parse_ini_file('../config.ini', true);
        } else {
            throw new \Exception("Cannot parse configurations file (config.ini)");
        }
    }

    /**
     * @param string $section
     * @throws \Exception
     */
    private static function validateRequiredSection($section)
    {
        if (!isset(self::$config[$section])) {
            throw new \Exception("Required configuration section [$section] is not defined");
        }
    }

    /**
     * @param string $section
     * @param string $field
     * @throws \Exception
     */
    private static function validateRequiredConfigurationField($section, $field)
    {
        if (!isset(self::$config[$section][$field])) {
            throw new \Exception("Required configuration field [$field] in section $section is not defined");
        }
    }
}