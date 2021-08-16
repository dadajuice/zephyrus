<?php namespace Zephyrus\Database;

use stdClass;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseStatement;
use Zephyrus\Database\Core\Filterable;
use Zephyrus\Database\Core\Pageable;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Security\Cryptography;

abstract class DatabaseBroker
{
    /**
     * @var Database
     */
    private $database;

    /**
     * @var callable
     */
    private $sanitizeCallback = null;

    /**
     * @var array
     */
    private $encryptedFields = [];

    use Pageable;
    use Filterable { filterQuery as private; }

    /**
     * Broker constructor called by children. Simply get the database reference for further use.
     *
     * @param null|Database $database
     * @throws DatabaseException
     */
    public function __construct(?Database $database = null)
    {
        $this->database = $database;
        if (is_null($this->database)) {
            $this->database = DatabaseFactory::buildFromConfigurations();
        }
    }

    /**
     * @return Database
     */
    protected function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * @param Database $database
     */
    protected function setDatabase(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Proceeds to include the given variable into the database environnement so that the executed queries, triggers or
     * stored procedures could have access to the variable. Useful for example to pass a user id to register for
     * automated log triggers.
     *
     * @param string $name
     * @param string $value
     */
    protected function addSessionVariable(string $name, string $value)
    {
        $this->query($this->database->getAdapter()->getAddEnvironmentVariableClause($name, $value));
    }

    public function isFieldEncrypted(string $field): bool
    {
        return in_array($field, $this->encryptedFields);
    }

    /**
     * @return array
     */
    public function getEncryptedFields(): array
    {
        return $this->encryptedFields;
    }

    public function setSanitizeCallback(?callable $callback)
    {
        $this->sanitizeCallback = $callback;
    }

    public function getSanitizeCallback(): ?callable
    {
        return $this->sanitizeCallback;
    }

    /**
     * @param array $fields
     */
    protected function setEncryptedFields(array $fields): void
    {
        $this->encryptedFields = $fields;
    }

    /**
     * Executes any type of query and simply returns the DatabaseStatement object ready to be fetched. Will throw a
     * DatabaseException is the query fails to execute.
     *
     * @param string $query
     * @param array $parameters
     * @return DatabaseStatement
     */
    protected function query(string $query, array $parameters = []): DatabaseStatement
    {
        return $this->prepareStatement($query, $parameters);
    }

    /**
     * Executes a SELECT query which should return a single data row. Best suited for queries involving primary key in
     * where. Will return null if the query did not fetch any result.
     *
     * @param string $query
     * @param array $parameters
     * @return stdClass|null
     */
    protected function selectSingle(string $query, array $parameters = []): ?stdClass
    {
        $statement = $this->prepareStatement($query, $parameters);
        $row = $statement->next();
        $this->decryptSensitiveFields($row);
        return $row;
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will return an empty array if the query
     * did not return any results.
     *
     * @param string $query
     * @param array $parameters
     * @param callable $callback
     * @return \stdClass[]
     */
    protected function select(string $query, array $parameters = [], ?callable $callback = null): array
    {
        $statement = $this->prepareStatement($query, $parameters);
        $results = [];
        while ($row = $statement->next()) {
            $this->decryptSensitiveFields($row);
            $results[] = (is_null($callback)) ? $row : $callback($row);
        }
        return $results;
    }

    /**
     * Execute a SELECT query which return the entire set of rows in an array. Will filter the query according to the
     * current filter loaded into the broker class. Returns null if the query did not fetch any result.
     *
     * @param string $query
     * @param array $parameters
     * @param callable|null $callback
     * @return array|stdClass[]
     */
    protected function filteredSelect(string $query, array $parameters = [], ?callable $callback = null): array
    {
        $query = $this->filterQuery($query);
        if (!is_null($this->pager)) {
            $query .= $this->pager->getSqlLimitClause($this->database->getAdapter());
        }
        return $this->select($query, $parameters, $callback);
    }

    /**
     * Executes a SELECT query which should return a single data row. Will filter the query according to the current
     * filter loaded into the broker class. Returns null if the query did not fetch any result.
     *
     * @param string $query
     * @param array $parameters
     * @return stdClass|null
     */
    protected function filteredSelectSingle(string $query, array $parameters = []): ?stdClass
    {
        $query = $this->filterQuery($query);
        return $this->selectSingle($query, $parameters);
    }

    /**
     * Execute a query which should be contain inside a transaction. The specified callback method will optionally
     * receive the Database instance if one argument is defined. Will work with nested transactions if using the
     * transaction PDO handler. Best suited method for INSERT, UPDATE and DELETE queries.
     *
     * @param callable $callback
     * @return mixed
     */
    protected function transaction(callable $callback)
    {
        try {
            $this->database->beginTransaction();
            $reflect = new \ReflectionFunction($callback);
            if ($reflect->getNumberOfParameters() == 1) {
                $result = $callback($this->database);
            } elseif ($reflect->getNumberOfParameters() == 0) {
                $result = $callback();
            } else {
                throw new \InvalidArgumentException("Specified callback must have 0 or 1 argument");
            }
            $this->database->commit();
            return $result;
        } catch (\Exception $e) {
            $this->database->rollback();

            throw new DatabaseException($e->getMessage());
        }
    }

    /**
     * Encrypts the given value with the encryption key defined in the database configurations.
     *
     * @param $value
     * @return string
     */
    protected function sensitize($value): string
    {
        $encryptionKey = Configuration::getDatabaseConfiguration('encryption_key');
        if (is_null($encryptionKey)) {
            throw new \RuntimeException("Encryption key hasn't been defined for database configuration"); // @codeCoverageIgnore
        }
        return Cryptography::encrypt($value, $encryptionKey);
    }

    /**
     * Proceeds to decrypt any flagged sensitive database fields with the encryption key defined in the database
     * configurations.
     *
     * @param $row
     */
    private function decryptSensitiveFields(&$row)
    {
        if ($row === false || empty($this->encryptedFields)) {
            return;
        }
        $encryptionKey = Configuration::getDatabaseConfiguration('encryption_key');
        if (is_null($encryptionKey)) {
            throw new \RuntimeException("Encryption key hasn't been defined for database configuration"); // @codeCoverageIgnore
        }
        foreach (get_object_vars($row) as $column => $value) {
            if (!is_null($value) && is_string($value) && $this->isFieldEncrypted($column)) {
                $plainText = Cryptography::decrypt($value, $encryptionKey) ?? "ENCRYPTED";
                $row->{$column} = $plainText;
            }
        }
    }

    private function prepareStatement(string $query, array $parameters = []): DatabaseStatement
    {
        $statement = $this->database->query($query, $parameters);
        if (!is_null($this->sanitizeCallback)) {
            $statement->setSanitizeCallback($this->sanitizeCallback);
        }
        return $statement;
    }
}
