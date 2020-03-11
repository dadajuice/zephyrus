<?php namespace Zephyrus\Database\Adapters;

use PDO;
use Zephyrus\Exceptions\DatabaseException;

class DatabaseAdapter
{
    /**
     * @var string
     */
    private $charset;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $database;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $dsn;

    public function __construct(string $dbms)
    {
        if (!in_array($dbms, PDO::getAvailableDrivers())) {
            throw new DatabaseException("Configured Database management system [$dbms] doesn't correspond 
                to one of the available drivers [" . implode(',', PDO::getAvailableDrivers()) . "]");
        }
    }

    public function getDriverName()
    {
        return "pgsql";
    }

    public function searchPattern(string $field, string $search)
    {
        $field = $this->purify($field);
        $search = $this->purify($search);
        return "($field LIKE %$search%)";
    }

    /**
     * Basic filtering to eliminate any tags and empty leading / trailing
     * characters.
     *
     * @param string $data
     * @return string
     */
    public function purify($data)
    {
        return htmlspecialchars(trim(strip_tags($data)), ENT_QUOTES | ENT_HTML401, 'UTF-8');
    }

    public function getDataSourceName()
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
