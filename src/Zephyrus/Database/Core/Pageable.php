<?php namespace Zephyrus\Database\Core;

use Zephyrus\Utilities\Pager;

trait Pageable
{
    /**
     * @var Pager
     */
    private $pager = null;

    /**
     * @var int
     */
    private $limit = Pager::DEFAULT_PAGE_MAX_ENTITIES;

    /**
     * @var string
     */
    private $parameterName = Pager::DEFAULT_URL_PARAMETER;

    /**
     * Overrides the default pager settings (page limit and the url parameter
     * name).
     *
     * @param int $limit
     * @param string $parameterName
     */
    public function configurePager(int $limit, string $parameterName)
    {
        $this->limit = $limit;
        $this->parameterName = $parameterName;
    }

    /**
     * Applies a Pager instance to the current broker. Meaning that all
     * subsequent queries will limit the results automatically.
     *
     * @param int $count
     */
    public function applyPager(int $count)
    {
        $this->pager = new Pager($count, $this->limit, $this->parameterName);
    }

    /**
     * Removes the applied pager meaning that any subsequent queries wont use
     * the pagination.
     */
    public function removePager()
    {
        $this->pager = null;
    }

    /**
     * @return Pager
     */
    public function getPager(): ?Pager
    {
        return $this->pager;
    }
}
