<?php namespace Zephyrus\Database;

use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseSession
{
    private static ?DatabaseSession $instance = null;
    private Database $database;

    /**
     * @throws FatalDatabaseException
     */
    final public static function initiate(array $configurations): void
    {
        static::$instance = new static(new Database($configurations));
    }

    final public static function kill(): void
    {
        static::$instance = null;
    }

    final public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            throw new RuntimeException("DatabaseSession instance must first be initialized with [DatabaseSession::initiate(Database \$databaseInstance)].");
        }
        return static::$instance;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    protected function __construct(Database $database)
    {
        $this->database = $database;
        $this->activateLocale();
        $this->activateSearchPath();
    }

    private function activateSearchPath(): void
    {
        $searchPaths = $this->database->getConfiguration()->getSearchPaths();
        if (empty($searchPaths)) {
            return;
        }
        $paths = implode(', ', $searchPaths);
        $this->database->query("SET search_path TO $paths;");
    }

    private function activateLocale(): void
    {
        $this->database->query("SET lc_time = '" . Configuration::getLocale('language') . ".UTF-8'");
    }
}
