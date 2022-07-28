<?php namespace Zephyrus\Database\Core;

use PDO;
use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseSource
{
    public const DEFAULT_CONFIGURATIONS = [
        'dbms' => 'sqlite', // PDO Driver to use
        'host' => 'localhost', // Specifies the database's server address (without port)
        'port' => '', // Port to access the database's server, leave empty to use the default port of your dbms
        'charset' => 'utf8', // Charset used for encoding
        'database' => ':memory:', // Database name (or filepath to database, :memory: for SQLite)
        'username' => '', // Username for database authentication
        'password' => '' // Password for database authentication
    ];

    private array $configurations;
    private string $dbms = '';
    private string $host = '';
    private string $port = '';
    private string $databaseName = '';
    private string $username = '';
    private string $password = '';
    private string $charset = '';

    /**
     * Retrieves the list of currently supported DBMS by Zephyrus.
     *
     * @return string[]
     */
    public static function getSupportedDrivers(): array
    {
        return ['sqlite', 'sqlite2', 'mysql', 'mariadb', 'pgsql'];
    }

    /**
     * Wrapper to retrieve the list of currently installed DBMS's drivers.
     *
     * @return array
     */
    public static function getAvailableDrivers(): array
    {
        return PDO::getAvailableDrivers();
    }

    /**
     * @throws FatalDatabaseException
     */
    public function __construct(array $configurations = self::DEFAULT_CONFIGURATIONS)
    {
        $this->initializeConfigurations($configurations);
        $this->initializeDbms();
        $this->initializeHost();
        $this->initializePort();
        $this->initializeDatabaseName();
        $this->initializeCharset();
        $this->initializeAuthentication();
    }

    /**
     * Retrieve the DBMS driver name. Will be one of the installed and supported drivers.
     *
     * @return string
     */
    public function getDatabaseManagementSystem(): string
    {
        return $this->dbms;
    }

    /**
     * Retrieve the configured host name indicating the network path to the database instance.
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Retrieve the configured database name to connect to once the adapter is built. Can be the filepath or ':memory:'
     * for SQLite databases.
     *
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * Retrieve the configured username (optional) for authentication purpose to the database.
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Retrieve the configured password (optional) for authentication purpose to the database.
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Retrieve the configured port (optional) to connect to the database instance.
     *
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * Retrieve the configured charset (optional) to use for the communication with the database. Will affect the result
     * set encoding.
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Retrieves the PDO compatible DSN string for connection purpose.
     *
     * @return string
     */
    public function getDatabaseSourceName(): string
    {
        $port = (!empty($this->getPort())) ? "port={$this->getPort()};" : "";
        return $this->getDatabaseManagementSystem() . ':dbname=' . $this->getDatabaseName() . ';host=' . $this->getHost() . ';' . $port;
    }

    private function initializeConfigurations(array $configurations)
    {
        $this->configurations = $configurations;
    }

    /**
     * @throws FatalDatabaseException
     */
    private function initializeDbms()
    {
        if (!isset($this->configurations['dbms'])) {
            throw FatalDatabaseException::missingConfiguration('dbms');
        }
        $this->dbms = $this->configurations['dbms'];
        if (!in_array($this->dbms, self::getAvailableDrivers())) {
            throw FatalDatabaseException::driverNotAvailable($this->dbms);
        }
        if (!in_array($this->dbms, self::getSupportedDrivers())) {
            throw FatalDatabaseException::driverNotSupported($this->dbms); // @codeCoverageIgnore
        }
    }

    /**
     * @throws FatalDatabaseException
     */
    private function initializeHost()
    {
        if (!isset($this->configurations['host'])) {
            throw FatalDatabaseException::missingConfiguration('host');
        }
        $this->host = $this->configurations['host'];
    }

    /**
     * @throws FatalDatabaseException
     */
    private function initializeDatabaseName()
    {
        if (!isset($this->configurations['database'])) {
            throw FatalDatabaseException::missingConfiguration('database');
        }
        $this->databaseName = $this->configurations['database'];
    }

    /**
     * @throws FatalDatabaseException
     */
    private function initializePort()
    {
        if (isset($this->configurations['port']) && $this->configurations['port']) {
            if (!is_numeric($this->configurations['port'])) {
                throw FatalDatabaseException::invalidPortConfiguration();
            }
            $this->port = $this->configurations['port'];
        }
    }

    private function initializeAuthentication()
    {
        if (isset($this->configurations['username']) && $this->configurations['username']) {
            $this->username = $this->configurations['username'];
        }
        if (isset($this->configurations['password']) && $this->configurations['password']) {
            $this->password = $this->configurations['password'];
        }
    }

    private function initializeCharset()
    {
        if (isset($this->configurations['charset']) && $this->configurations['charset']) {
            $this->charset = $this->configurations['charset'];
        }
    }
}
