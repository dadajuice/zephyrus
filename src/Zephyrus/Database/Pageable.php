<?php namespace Zephyrus\Database;

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
    private $limit = Pager::PAGE_MAX_ENTITIES;

    /**
     * @var string
     */
    private $parameterName = Pager::URL_PARAMETER;

    public function configurePager(int $limit, string $parameterName)
    {
        $this->limit = $limit;
        $this->parameterName = $parameterName;
    }

    /**
     * @param int $count
     */
    public function applyPager(int $count)
    {
        $this->pager = new Pager($count, $this->limit, $this->parameterName);
    }

    /**
     * @return Pager
     */
    public function getPager(): ?Pager
    {
        return $this->pager;
    }

    public function removePager()
    {
        $this->pager = null;
    }
}
