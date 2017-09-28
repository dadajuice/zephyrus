<?php namespace Zephyrus\Database;

abstract class BrokerApi extends Broker
{
    public function __construct(?Database $database = null)
    {
        parent::__construct($database);
        parent::setFetchStyle(\PDO::FETCH_OBJ);
    }
}
