<?php namespace Zephyrus\Database;

use PDO;
use PDOException;
use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\DatabaseException;

class Database
{
    private const DEFAULT_DBMS = 'mysql';

    /**
     * @var TransactionPDO
     */
    private $handle = null;

    /**
     * @var Database
     */
    private static $sharedInstance = null;

    /**
     * @var string
     */
    private $dsn;

    /**
     * Execute a parametrized SQL query. Parameters must be included as an
     * array compatible with the PDO query preparation.
     *
     * @param string $query
     * @param array $parameters
     * @throws DatabaseException
     * @return DatabaseStatement
     */
    public function query($query, $parameters = [])
    {
        try {
            $statement = $this->handle->prepare($query, [PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY]);
            $statement->execute($parameters);
            return new DatabaseStatement($statement);
        } catch (\PDOException $e) {
            throw new DatabaseException('Error while preparing query « ' . $query . ' » (' .
                $e->getMessage() . ')', $query);
        }
    }

    /**
     * Disable auto-commit mode and begin an SQL transaction. When this method
     * is called, database will only be updated when calling the <commit>
     * method. Calling the <rollback> method will undo any SQL commands done
     * within the started transaction.
     *
     * @see self::commit()
     * @see self::rollback()
     */
    public function beginTransaction()
    {
        $this->handle->beginTransaction();
    }

    /**
     * Manually commit a started SQL transaction. After a successful commit, the
     * connection handler will return auto-commit mode.
     *
     * @throws DatabaseException
     */
    public function commit()
    {
        try {
            $this->handle->commit();
        } catch (\PDOException $e) {
            throw new DatabaseException("Couldn't commit SQL transaction. Are you sure a transaction 
                has been started ?");
        }
    }

    /**
     * Cancel any SQL commands done within a started SQL transaction. After a
     * successful rollback, the connection handler will return auto-commit
     * mode.
     *
     * @throws DatabaseException
     */
    public function rollback()
    {
        try {
            $this->handle->rollBack();
        } catch (\PDOException $e) {
            throw new DatabaseException("Couldn't rollback SQL transaction. Are you sure a transaction 
                has been started ?");
        }
    }

    /**
     * @return string
     */
    public function getLastInsertedId()
    {
        return $this->handle->lastInsertId();
    }

    /**
     * @return string
     */
    public function getDataSourceName(): string
    {
        return $this->dsn;
    }

    /**
     * @return string
     */
    public function getDatabaseManagementSystem(): string
    {
        return $this->handle->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Manual database instance constructor.
     *
     * @param string $dsn
     * @param string $username
     * @param string $password
     * @throws DatabaseException
     */
    public function __construct(string $dsn, string $username = "", string $password = "")
    {
        $this->dsn = $dsn;
        try {
            $this->handle = new TransactionPDO($dsn, $username, $password);
            $this->handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new DatabaseException("Connection failed to database : " . $e->getMessage());
        }
    }

    /**
     * Constructs a database instance from the defined configurations.
     *
     * @param array|null $configuration
     * @return Database
     * @throws DatabaseException
     */
    public static function buildFromConfiguration(?array $configuration = null): Database
    {
        $config = $configuration ?? Configuration::getDatabaseConfiguration();
        if (!is_null(self::$sharedInstance) && ($config['shared'] ?? false)) {
            return self::$sharedInstance;
        }
        $instance = self::initializeFromConfiguration($config);
        if ($config['shared'] ?? false) {
            self::$sharedInstance = $instance;
        }
        return $instance;
    }

    /**
     * @param array $config
     * @return Database
     * @throws DatabaseException
     */
    private static function initializeFromConfiguration(array $config): Database
    {
        $dsn = self::buildDataSourceName($config);
        return new self($dsn, $config['username'] ?? null, $config['password'] ?? null);
    }

    /**
     * Mandatory fields to use :
     * database (database name to connect to)
     * host
     *
     *
     * @param array $config
     * @throws DatabaseException
     * @return mixed|string
     */
    private static function buildDataSourceName(array $config)
    {
        $dbms = $config['dbms'] ?? self::DEFAULT_DBMS;
        if (!in_array($dbms, PDO::getAvailableDrivers())) {
            throw new DatabaseException("Configured Database management system [$dbms] doesn't correspond 
                to one of the available drivers [" . implode(',', PDO::getAvailableDrivers()) . "]");
        }
        $charset = (isset($config['charset'])) ? ";charset={$config['charset']};" : "";
        $port = (isset($config['port'])) ? ";port={$config['port']};" : "";
        return $config['dsn']
            ?? $dbms . ':dbname=' . $config['database'] . ';host=' . $config['host'] . $port . $charset;
    }
}
