<?php namespace Zephyrus\Database\Brokers;

use Zephyrus\Database\Core\DatabaseStatement;

class SqlResult
{
    private DatabaseStatement $statement;

    public function __construct(DatabaseStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Process the result set one row at a time. The given callback will receive each row. Processing can end if the
     * callback returns false.
     *
     * @param callable $callback
     * @return void
     */
    public function stream(callable $callback): void
    {
        while ($row = $this->statement->next()) {
            $continueProcessing = $callback($row);
            if (!$continueProcessing) {
                break;
            }
        }
    }

    /**
     * Process the result set a chunk of rows at a time (default 50 rows). The callback will receive an array of
     * rows. Processing can end if the callback returns false.
     *
     * @param callable $callback
     * @param int $count
     * @return void
     */
    public function chunks(callable $callback, int $count = 50): void
    {
        $results = [];
        $iterator = 0;
        while ($row = $this->statement->next()) {
            $results[] = $row;
            $iterator++;
            if ($iterator == $count) {
                $continueProcessing = $callback($results);
                $results = [];
                if (!$continueProcessing) {
                    break;
                }
            }
        }
    }

    /**
     * Warning. Could be very slow and harmful as it will load the entire result set in memory. If a callback is given,
     * it will be executed for each row.
     *
     * @param callable|null $callback
     * @return array
     */
    public function toArray(?callable $callback = null): array
    {
        $results = [];
        while ($row = $this->statement->next()) {
            $results[] = (is_null($callback)) ? $row : $callback($row);
        }
        return $results;
    }
}
