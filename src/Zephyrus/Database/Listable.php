<?php namespace Zephyrus\Database;

interface Listable
{
    /**
     * Retrieves the total number of available rows for the corresponding
     * findAll method while applying the filters (sort, order and
     * search). The filteredSelectSingle method should be used. E.g.:.
     *
     *     return $this->filteredSelectSingle("SELECT COUNT(*) as n FROM user")->n;
     *
     * @return int
     */
    public function count(): int;

    /**
     * Retrieves all the rows while applying the filters (sort, order and
     * search). The filteredSelect method should be used. E.g.:.
     *
     *     return $this->filteredSelect("SELECT * FROM user");
     *
     * @return array
     */
    public function findAll(): array;

    /**
     * Must provide the search query respecting the established convention by
     * using « :search » to identify search values. E.g.:.
     *
     *     return "(username LIKE :search OR email LIKE :search OR CONCAT(firstname, ' ', lastname) LIKE :search)";
     *
     * The above example would allow search on the username, email and name
     * fields.
     *
     * @return string
     */
    public function search(): string;

    /**
     * Must provide correspondence between sort label used on the front end (as
     * table header links) and database columns. If the sort label and column
     * are the same, its not necessary to include them in the resulting array.
     * Only the different ones must be identified (key = sort label,
     * value = sort column). E.g.:.
     *
     *     return [
     *         'name' => 'firstname $order, lastname',
     *         'login' => 'last_login'
     *     ];
     *
     * This method is forced as abstract because it is not a good behavior to
     * expose database columns as sort labels and also gives opportunity to
     * properly translate the sort labels if needed.
     *
     * @param string $order
     * @return string[]
     */
    public function sort(string $order): array;
}
