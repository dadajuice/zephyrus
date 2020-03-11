<?php namespace Zephyrus\Database\Adapters;

class MysqlAdapter
{


    public function addSessionVariable(string $name, string $value)
    {
        parent::query("SET @$name = ?", [$value]);
    }
}
