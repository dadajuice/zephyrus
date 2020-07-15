<?php namespace Zephyrus\Database\Core\Adapters;

use PDO;
use PDOException;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\TransactionPDO;
use Zephyrus\Exceptions\DatabaseException;

abstract class DatabaseAdapter
{
    /**
     * @var string
     */
    private $dbms;

    /**
     * @var string
     */
    private $databaseName;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $charset;

    /**
     * @var string
     */
    private $dsn;

    /**
     * @throws DatabaseException
     * @return PDO
     */
    public function buildHandle(): \PDO
    {
        if (!in_array($this->dbms, PDO::getAvailableDrivers())) {
            throw new DatabaseException("Configured Database management 
                system [{$this->dbms}] doesn't correspond to one of the available 
                drivers [" . implode(',', PDO::getAvailableDrivers()) . "]");
        }

        try {
            $handle = new TransactionPDO($this->dsn, $this->username, $this->password);
            $handle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $handle;
        } catch (PDOException $e) {
            throw new DatabaseException("Connection failed to database : " . $e->getMessage());
        }
    }

    /**
     * @param array $configurations
     * @throws DatabaseException
     */
    public function __construct(array $configurations)
    {
        if (!array_key_exists('dbms', $configurations)) {
            throw new \InvalidArgumentException("The [dbms] database configuration option is required");
        }
        $this->dbms = $configurations['dbms'];
        $this->username = $configurations['username'] ?? "";
        $this->password = $configurations['password'] ?? "";
        $this->databaseName = $configurations['database'] ?? "";
        $this->host = $configurations['host'] ?? "";
        $this->charset = $configurations['charset'] ?? "";
        $this->port = $configurations['port'] ?? "";
        $this->dsn = $this->buildDataSourceName();
    }

    /**
     * @param string $field
     * @param string $search
     * @return string
     */
    public function getSearchFieldClause(string $field, string $search): string
    {
        $search = $this->purify($search);
        return "($field LIKE '%$search%')";
    }

    /**
     * @param int $offset
     * @param int $maxEntities
     * @return string
     */
    public function getLimitClause(int $offset, int $maxEntities): string
    {
        return " LIMIT $offset, $maxEntities";
    }

    /**
     * @param string $name
     * @param string $value
     * @return string
     */
    public function getAddEnvironmentVariableClause(string $name, string $value): string
    {
        return "SET @$name = '$value'";
    }

    /**
     * Basic filtering to eliminate any tags and empty leading / trailing
     * characters.
     *
     * @param string $data
     * @return string
     */
    public function purify(string $data): string
    {
        return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES | ENT_HTML401, 'UTF-8');
    }

    /**
     * @return string
     */
    public function getDatabaseManagementSystem(): string
    {
        return $this->dbms;
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @return string
     */
    public function getPort(): string
    {
        return $this->port;
    }

    /**
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * @return string
     */
    public function getDataSourceName(): string
    {
        return $this->dsn;
    }

    /**
     * Meta query to retrieve all table names of given database instance. Must be redefined in children adapter classes
     * to adapt for each supported DBMS. Should return only an array with the table names as value (e.g. ['user',
     * 'client']).
     *
     * @param Database $database
     * @return array
     */
    public function getAllTableNames(Database $database): array
    {
        return [];
    }

    /**
     * Meta query to retrieve all table details of given database instance. Must be redefined in children adapter
     * classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @return array
     */
    public function getAllTables(Database $database): array
    {
        $results = [];
        foreach ($this->getAllTableNames($database) as $name) {
            $results[] = (object) [
                'name' => $name,
                'columns' => $this->getAllColumns($database, $name),
                'constraints' => $this->getAllConstraints($database, $name)
            ];
        }
        return $results;
    }

    /**
     * Meta query to retrieve all column names of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return only an array with the
     * columns names as value (e.g. ['firstname', 'lastname']).
     *
     * @param Database $database
     * @param string $tableName
     * @return array
     */
    public function getAllColumnNames(Database $database, string $tableName): array
    {
        return [];
    }

    public function getAllConstraints(Database $database, string $tableName): array
    {
        return [];
    }

    /**
     * Meta query to retrieve all column details of given table name within the specified database instance. Must be
     * redefined in children adapter classes to adapt for each supported DBMS. Should return an array of stdClass.
     *
     * @param Database $database
     * @param string $tableName
     * @return array
     */
    public function getAllColumns(Database $database, string $tableName): array
    {
        return [];
    }

    protected function buildDataSourceName(): string
    {
        $charset = (!empty($this->charset)) ? "charset={$this->charset};" : "";
        $port = (!empty($this->port)) ? "port={$this->port};" : "";
        return $this->dbms . ':dbname=' . $this->databaseName . ';host=' . $this->host . ';' . $port . $charset;
    }
}
