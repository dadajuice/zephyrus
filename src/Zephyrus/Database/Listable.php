<?php namespace Zephyrus\Database;

use Zephyrus\Utilities\Filter;
use Zephyrus\Utilities\Pager;

interface Listable
{
    public function findAll(): array;

    public function count(): int;

    public function getFilter(): ?Filter;

    public function applyFilter(string $defaultSort = "", string $defaultOrder = "");

    public function buildPager($count, $limit = Pager::PAGE_MAX_ENTITIES, $urlParameter = Pager::URL_PARAMETER);
}
