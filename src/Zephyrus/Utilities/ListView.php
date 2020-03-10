<?php namespace Models;

use stdClass;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Filter;
use Zephyrus\Utilities\Pager;

class ListView
{
    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Pager
     */
    private $pager;

    /**
     * @var stdClass
     */
    private $resultSet;

    /**
     * Should be directly instantiated from an API call or a broker result and
     * include the following structure.
     *
     * {
     *   "results": {
     *     "rows": [{...}, {...}, ...],
     *     "count": 2,
     *     "totalCount": 5,
     *   },
     *   "pager": {
     *     "maxPage": 1,
     *     "currentPage": 1,
     *     "maxEntitiesPerPage": 50
     *   },
     *   "filter": {
     *     "search": "te",
     *     "sort": "",
     *     "order": "asc"
     *   },
     *   "data": {...} // optional
     * }
     *
     * @param stdClass $resultSet
     */
    public function __construct(stdClass $resultSet)
    {
        $this->resultSet = $resultSet;
        $this->filter = new Filter(RequestFactory::read(), $resultSet->filter->sort, $resultSet->filter->order);
        $this->pager = new Pager($this->resultSet->results->count, $this->resultSet->pager->maxEntitiesPerPage);
    }

    /**
     * Highlights matching search into the given data string.
     *
     * @param string|null $data
     * @return string
     */
    public function mark(?string $data): string
    {
        $pattern = "/" . preg_quote($this->filter->getSearch(), '/') . "/i";
        return (!$this->filter->getSearch()) ? $data : preg_replace($pattern, "<mark>$0</mark>", $data);
    }

    /**
     * @return string
     */
    public function getSort(): string
    {
        return $this->filter->getSort();
    }

    /**
     * @return string
     */
    public function getOrder(): string
    {
        return $this->filter->getOrder();
    }

    /**
     * @return string
     */
    public function getSearch(): string
    {
        return $this->filter->getSearch();
    }

    /**
     * @return int
     */
    public function getCurrentRowCount(): int
    {
        return $this->resultSet->results->count;
    }

    /**
     * @return int
     */
    public function getTotalRowCount(): int
    {
        return $this->resultSet->results->totalCount;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @param string $label
     * @param string $sort
     * @param string $align
     */
    public function addHeader(string $label, ?string $sort = null, string $align = "")
    {
        $this->headers[] = (object) [
            'title' => $label,
            'sort' => $sort,
            'align' => $align
        ];
    }

    /**
     * @return stdClass[]
     */
    public function getRows(): array
    {
        return $this->resultSet->results->rows;
    }

    /**
     * @return Pager
     */
    public function getPager(): ?Pager
    {
        return $this->pager;
    }

    public function getAdditionalData(): ?stdClass
    {
        return (isset($this->resultSet->data)) ? $this->resultSet->data : null;
    }
}
