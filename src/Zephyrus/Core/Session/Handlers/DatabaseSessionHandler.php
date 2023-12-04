<?php namespace Zephyrus\Core\Session\Handlers;

use SessionHandler;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\Session\SessionDatabaseStructureException;
use Zephyrus\Exceptions\Session\SessionDatabaseTableException;

class DatabaseSessionHandler extends SessionHandler
{
    private Database $database;
    private string $table;

    public function __construct(Database $database, string $table = "public.session")
    {
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * @throws SessionDatabaseStructureException
     * @throws SessionDatabaseTableException
     */
    public function isAvailable(): bool
    {
        list($schema, $table) = (str_contains($this->table, "."))
            ? explode('.', $this->table)
            : ['public', $this->table];
        if ($this->database->getSchemaInterrogator()->tableExists($table, $schema)) {
            $columns = ['session_id', 'access', 'data'];
            foreach ($columns as $column) {
                if (!$this->database->getSchemaInterrogator()->columnExists($column, $table, $schema)) {
                    throw new SessionDatabaseStructureException($table, $schema);
                }
            }
            return true;
        }

        throw new SessionDatabaseTableException($table, $schema);
    }

    public function destroy(string $id): bool
    {
        $this->database->query("DELETE FROM $this->table WHERE session_id = ?", [$id]);
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function gc(int $max_lifetime): int
    {
        $old = time() - $max_lifetime;
        $statement = $this->database->query("DELETE FROM $this->table WHERE access < ?", [$old]);
        return $statement->count();
    }

    public function open(string $path, string $name): bool
    {
        return $this->isAvailable();
    }

    public function read(string $id): string
    {
        $statement = $this->database->query("SELECT data FROM $this->table WHERE session_id = ?", [$id]);
        return $statement->next()?->data ?? "";
    }

    public function write(string $id, string $data): bool
    {
        $access = time();
        $sql = "INSERT INTO $this->table(session_id, access, data) 
                     VALUES (?, ?, ?) ON CONFLICT (session_id) DO UPDATE
                     SET access = excluded.access, 
                         data = excluded.data";
        $this->database->query($sql, [$id, $access, $data]);
        return true;
    }
}
