<?php namespace Models\Brokers;

use Models\Item;
use Zephyrus\Database\Broker;

class ItemBroker extends Broker
{
    private static $SQL_SELECT_ALL = "SELECT * FROM item";
    private static $SQL_INSERT = "INSERT INTO item (name, price) VALUES(?, ?)";

    /**
     * @return Item[]
     */
    public function findAll()
    {
        $results = [];
        foreach ($this->selectAll(self::$SQL_SELECT_ALL) as $row) {
            $results[] = $this->load($row);
        }
        return $results;
    }

    public function insert(Item &$item)
    {
        // Very simple SQL call, use $this->transaction for multiple operations
        $this->query(self::$SQL_INSERT, [$item->getName(), $item->getPrice()]);
        $item->setId($this->getDatabase()->getLastInsertedId());
    }

    private function load(array $row) : Item
    {
        $item = new Item();
        $item->setId($row['id_item']);
        $item->setName($row['name']);
        $item->setPrice($row['price']);
        return $item;
    }
}