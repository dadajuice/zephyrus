<?php namespace Zephyrus\Database;

use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseSession
{
    private static ?DatabaseSession $instance = null;
    private Database $database;
    private array $searchPaths;

    /**
     * @throws FatalDatabaseException
     */
    final public static function initiate(array $configurations, array $searchPaths = ['public'])
    {
        self::$instance = new self(new Database($configurations), $searchPaths);
    }

    final public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            throw new RuntimeException("DatabaseSession instance must first be initialized with [DatabaseSession::initiate(Database \$databaseInstance)].");
        }
        return self::$instance;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getSearchPaths(): array
    {
        return $this->searchPaths;
    }

    private function __construct(Database $database, array $searchPaths)
    {
        $this->database = $database;
        $this->searchPaths = $searchPaths;
        $this->activateSearchPath();
        $this->activateLocale();
    }

    private function activateSearchPath()
    {
        if (empty($this->searchPaths)) {
            return;
        }
        $paths = implode(', ', $this->searchPaths);
        $this->database->query("SET search_path TO $paths;");
    }

    private function activateLocale()
    {
        $this->database->query("SET lc_time = '" . Configuration::getLocaleConfiguration('language') . ".UTF-8'");
    }
}
