<?php namespace Zephyrus\Tests\utilities;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\ListView;

class ListViewTest extends TestCase
{
    public function testSearch()
    {
        $list = $this->buildList();
        self::assertEquals('man', $list->getSearch());
    }

    public function testSort()
    {
        $list = $this->buildList();
        self::assertEquals('name', $list->getSort());
        self::assertEquals('asc', $list->getOrder());
    }

    public function testPager()
    {
        $list = $this->buildList();
        self::assertEquals(50, $list->getPager()->getMaxEntitiesPerPage());
    }

    public function testRows()
    {
        $list = $this->buildList();
        self::assertEquals(2, count($list->getRows()));
        self::assertEquals(2, $list->getCurrentRowCount());
        self::assertEquals(2, $list->getTotalRowCount());
    }

    public function testHeaders()
    {
        $list = $this->buildList();
        $list->addHeader('name', 'name');
        $list->addHeader('power', 'pwd', 'right');
        self::assertEquals(2, count($list->getHeaders()));
        $list->setHeaders([
            (object) [
                'title' => 'name',
                'sort' => 'alias',
                'align' => 'left'
            ]
        ]);
        self::assertEquals(1, count($list->getHeaders()));
    }

    public function testMark()
    {
        $list = $this->buildList();
        self::assertEquals('bat<mark>man</mark>', $list->mark('batman'));
    }

    public function testAdditionalData()
    {
        $list = $this->buildList();
        self::assertEquals(null, $list->getAdditionalData());
    }

    public function buildList()
    {
        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'name']]);
        return new ListView((object) [
            'results' => (object) [
                'rows' => [(object)['name' => 'batman'], (object)['name' => 'aquaman']],
                'count' => 2,
                'totalCount' => 2,
            ],
            'pager' => (object) [
                'maxPage' => 1,
                'currentPage' => 1,
                'maxEntitiesPerPage' => 50
            ],
            'filter' => (object) [
                'search' => 'man',
                'sort' => 'name',
                'order' => 'asc'
            ]
        ], $r);
    }
}
