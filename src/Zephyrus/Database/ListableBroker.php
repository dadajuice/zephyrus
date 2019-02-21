<?php namespace Zephyrus\Database;

use Models\Brokers\Listable;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Filter;

abstract class ListableBroker extends Broker implements Listable
{
    /**
     * @var Filter
     */
    private $filter = null;

    /**
     * Retrieves the total number of available rows for the corresponding
     * findAll method while applying the filters (sort, order and
     * search). The filteredSelectSingle method should be used. E.g.:.
     *
     *     return $this->filteredSelectSingle("SELECT COUNT(*) as n FROM user")->n;
     *
     * @return int
     */
    abstract public function count(): int;

    /**
     * Retrieves all the rows while applying the filters (sort, order and
     * search). The filteredSelect method should be used. E.g.:.
     *
     *     return $this->filteredSelect("SELECT * FROM user");
     *
     * @return array
     */
    abstract public function findAll(): array;

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
    abstract protected function search(): string;

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
    abstract protected function sort(string $order): array;

    /**
     * @return null | Filter
     */
    public function getFilter(): ?Filter
    {
        return $this->filter;
    }

    /**
     * Creates the filter according to the request.
     *
     * @param string $defaultSort
     * @param string $defaultOrder
     */
    public function applyFilter(string $defaultSort = "", string $defaultOrder = "")
    {
        $this->filter = new Filter(RequestFactory::read(), $defaultSort, $defaultOrder);
    }

    public function removeFilter()
    {
        $this->filter = null;
    }

    /**
     * @param string $query
     * @param array $parameters
     * @param string $allowedTags
     * @return \stdClass | null
     */
    protected function filteredSelectSingle(string $query, array $parameters = [], string $allowedTags = ""): ?\stdClass
    {
        return $this->filteredQuery(true, $query, $parameters, $allowedTags);
    }

    /**
     * @param string $query
     * @param array $parameters
     * @param string $allowedTags
     * @return \stdClass[]
     */
    protected function filteredSelect(string $query, array $parameters = [], string $allowedTags = ""): array
    {
        return $this->filteredQuery(false, $query, $parameters, $allowedTags);
    }

    private function filteredQuery(bool $single, string $query, array $parameters = [], string $allowedTags = "")
    {
        if (!is_null($this->filter)) {
            $search = $this->filter->getSearch();
            if (!is_null($search)) {
                $search = "%$search%";
                $query = $this->buildSearch($query);
            }
            $query .= $this->buildOrderBy();
            $parameters = array_merge($parameters, ['search' => $search]);
        }
        return ($single)
            ? $this->selectSingle($query, $parameters, $allowedTags)
            : $this->select($query, $parameters, $allowedTags);
    }

    private function buildSearch(string $query)
    {
        $lastWhereByOccurrence = strripos($query, "where");
        $lastGroupByOccurrence = strripos($query, "group by");
        $lastHavingByOccurrence = strripos($query, "having");
        $insertionPosition = strlen($query);
        if ($lastGroupByOccurrence !== false && $lastGroupByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastGroupByOccurrence;
        } elseif ($lastHavingByOccurrence !== false && $lastHavingByOccurrence > $lastWhereByOccurrence) {
            $insertionPosition = $lastHavingByOccurrence;
        }
        $begin = substr($query, 0, $insertionPosition);
        $end = substr($query, $insertionPosition);
        return $begin . $this->buildWhere($query) . $end;
    }

    private function buildWhere(string $query): string
    {
        $lastWhereByOccurrence = strripos($query, "where");
        return (($lastWhereByOccurrence !== false) ? " AND " : " WHERE ") . $this->search();
    }

    private function buildOrderBy(): string
    {
        $sort = $this->filter->getSort();
        if (!empty($sort)) {
            $order = $this->filter->getOrder();
            $convertedSorts = $this->sort($order);
            $orderBy = (array_key_exists($sort, $convertedSorts)) ? $convertedSorts[$sort] : $sort;
            return " ORDER BY $orderBy $order";
        }
        return "";
    }
}
