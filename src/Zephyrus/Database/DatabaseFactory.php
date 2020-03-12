<?php namespace Zephyrus\Database;

use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Adapters\DatabaseAdapter;
use Zephyrus\Database\Core\Adapters\MysqlAdapter;
use Zephyrus\Database\Core\Adapters\PostgresqlAdapter;
use Zephyrus\Database\Core\Adapters\SqliteAdapter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseFactory
{
    /**
     * @var Database
     */
    private static $sharedInstance = null;

    /**
     * @param array $configurations
     * @throws DatabaseException
     * @return Database
     */
    public static function buildFromConfigurations(?array $configurations = null): Database
    {
        $configurations = $configurations ?? Configuration::getDatabaseConfiguration();
        if (!is_null(self::$sharedInstance) && ($configurations['shared'] ?? false)) {
            return self::$sharedInstance;
        }
        if (!key_exists('dbms', $configurations)) {
            throw new \InvalidArgumentException("The [dbms] database configuration option is required");
        }
        $adapter = self::buildAdapter($configurations);
        if (is_null($adapter)) {
            throw new DatabaseException("No adapter found for the given database management system [{$configurations['dbms']}]");
        }
        $instance = new Database($adapter);
        if ($configurations['shared'] ?? false) {
            self::$sharedInstance = $instance;
        }
        return $instance;
    }

    /**
     * @param array $configurations
     * @throws DatabaseException
     * @return DatabaseAdapter|null
     */
    private static function buildAdapter(array $configurations): ?DatabaseAdapter
    {
        if (in_array($configurations['dbms'], MysqlAdapter::DBMS)) {
            return new MysqlAdapter($configurations);
        }
        if (in_array($configurations['dbms'], PostgresqlAdapter::DBMS)) {
            return new PostgresqlAdapter($configurations);
        }
        if (in_array($configurations['dbms'], SqliteAdapter::DBMS)) {
            return new SqliteAdapter($configurations);
        }
        return null;
    }
}
