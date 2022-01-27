<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Utilities\Filter;

class FilterTest extends TestCase
{
    public function testSearch()
    {
        $filter = new Filter($this->getRequest());
        self::assertTrue($filter->hasSearch());
        self::assertEquals("test", $filter->getSearch());
    }

    public function testSort()
    {
        $filter = new Filter($this->getRequest());
        self::assertEquals("username", $filter->getSort());
    }

    public function testOrder()
    {
        $filter = new Filter($this->getRequest());
        self::assertEquals("asc", $filter->getOrder());
    }

    public function testPage()
    {
        $filter = new Filter($this->getRequest());
        self::assertEquals(5, $filter->getPage());
    }

    public function testRemoveSort()
    {
        $filter = new Filter($this->getRequest());
        self::assertEquals("username", $filter->getSort());
        $filter->removeSort();
        self::assertFalse($filter->hasSort());
    }

    public function testRemoveSearch()
    {
        $filter = new Filter($this->getRequest());
        self::assertEquals("test", $filter->getSearch());
        $filter->removeSearch();
        self::assertFalse($filter->hasSearch());
    }

    public function testInvalidPage()
    {
        $filter = new Filter(new Request('http://test.local', 'GET', ['parameters' => ['page' => "error"]]));
        self::assertFalse($filter->hasSearch());
        self::assertEquals(1, $filter->getPage());
    }

    public function testInvalidOrder()
    {
        $filter = new Filter(new Request('http://test.local', 'GET', ['parameters' => ['order' => "error"]]));
        self::assertEquals("asc", $filter->getOrder());
    }

    private function getRequest()
    {
        return new Request('http://test.local', 'GET', ['parameters' => ['page' => 5, 'search' => 'test', 'order' => 'asc', 'sort' => 'username']]);
    }
}