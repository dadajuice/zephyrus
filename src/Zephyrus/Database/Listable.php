<?php namespace Zephyrus\Database;

interface Listable
{
    /**
     * Retrieves the total number of available rows for the corresponding
     * findAll method while applying the filters (sort, order and
     * search). The filteredSelectSingle method should be used. E.g.:.
     *
     *     return $this->selectSingle("SELECT COUNT(*) as n FROM user")->n;
     *
     * @return int
     */
    public function count(): int;

    /**
     * Retrieves all the rows while applying the filters (sort, order and
     * search). The filteredSelect method should be used. E.g.:.
     *
     *     return $this->select("SELECT * FROM user");
     *
     * @return array
     */
    public function findAll(): array;
}
