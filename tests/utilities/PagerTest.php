<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Pager;

class PagerTest extends TestCase
{
    public function testLimit()
    {
        $req = new Request('http://test.local/3?page=5', 'GET', ['id' => '3', 'page' => 5]);
        RequestFactory::set($req);
        $pager = new Pager(1000);
        $max = $pager->getMaxEntitiesPerPage();
        self::assertEquals(Pager::PAGE_MAX_ENTITIES, $max);
        $limit = $pager->getSqlLimit();
        self::assertEquals(" LIMIT 200, 50", $limit);
    }
}