<?php namespace Zephyrus\Database\Adapters;

class PostgresqlAdapter
{
    // myapp.user
    public function addSessionVariable(string $name, string $value)
    {
        parent::query("set session \"$name\" = '" . $value . "';");
    }

    public function getDriverName()
    {
        return "pgsql";
    }
}
