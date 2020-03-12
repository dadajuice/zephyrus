<?php namespace Zephyrus\Database;

use stdClass;
use Zephyrus\Utilities\Pager;

abstract class Broker
{
    public static function list(DatabaseBroker $broker, string $defaultSort = "", string $defaultOrder = "asc", int $pagerLimit = Pager::DEFAULT_PAGE_MAX_ENTITIES): stdClass
    {
        if (!($broker instanceof Listable)) {
            throw new \RuntimeException("Provided broker must implements the Listable instance");
        }

        $totalCount = $broker->count();
        $broker->applyFilter($defaultSort, $defaultOrder);
        $rows = $broker->findAll();
        $count = $broker->count();
        if ($pagerLimit > 0) {
            $broker->applyPager($count);
        }

        return (object) [
            'results' => (object) [
                'rows' => $rows,
                'count' => $count,
                'totalCount' => $totalCount,
            ],
            'pager' => (object) [
                'maxPage' => (!is_null($broker->getPager())) ? $broker->getPager()->getMaxPage() : 0,
                'currentPage' => (!is_null($broker->getPager())) ? $broker->getPager()->getCurrentPage() : 0,
                'maxEntitiesPerPage' => (!is_null($broker->getPager())) ? $broker->getPager()->getMaxEntitiesPerPage() : 0
            ],
            'filter' => (object) [
                'search' => $broker->getFilter()->getSearch(),
                'sort' => $broker->getFilter()->getSort(),
                'order' => $broker->getFilter()->getOrder()
            ]
        ];
    }
}
