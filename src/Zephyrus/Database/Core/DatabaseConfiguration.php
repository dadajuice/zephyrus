<?php namespace Zephyrus\Database\Core;

use PDO;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseConfiguration
{
    public const DEFAULT_CONFIGURATIONS = [
        'hostname' => 'localhost', // Specifies the database's server address (without port)
        'port' => '', // Port to access the database's server, leave empty to use the default port of your dbms
        'database' => '', // Database name
        'username' => '', // Username for database authentication
        'password' => '' // Password for database authentication
    ];

    private array $configurations;
    private string $hostname = '';
    private string $port = '';
    private string $databaseName = '';
    private string $username = '';
    private string $password = '';

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
        $this->initializeHostname();
        $this->initializePort();
        $this->initializeDatabaseName();
        $this->initializeAuthentication();
    }

    /**
     * Retrieve the configured host name indicating the network path to the database instance.
     *
     * @return string
     */
    public function getHostname(): string
    {
        return $this->hostname;
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
     * Retrieves the PDO compatible DSN string for connection purpose.
     *
     * @return string
     */
    public function getDatabaseSourceName(): string
    {
        $port = (!empty($this->getPort())) ? "port={$this->getPort()};" : "";
        return 'pgsql:dbname=' . $this->getDatabaseName() . ';host=' . $this->getHostname() . ';' . $port;
    }

    private function initializeConfigurations(array $configurations)
    {
        $this->configurations = $configurations;
    }

    /**
     * @codeCoverageIgnore
     * @throws FatalDatabaseException
     */
    private function initializeDbms()
    {
        if (!in_array('pgsql', self::getAvailableDrivers())) {
            throw FatalDatabaseException::driverNotAvailable('pgsql');
        }
    }

    /**
     * @throws FatalDatabaseException
     */
    private function initializeHostname()
    {
        if (!isset($this->configurations['hostname'])) {
            throw FatalDatabaseException::missingConfiguration('hostname');
        }
        $this->hostname = $this->configurations['hostname'];
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
}
