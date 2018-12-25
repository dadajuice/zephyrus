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

    public function testInvalidPage()
    {
        $filter = new Filter(new Request('http://test.local', 'GET', ['parameters' => ['page' => "error"]]));
        self::assertFalse($filter->hasSearch());
        self::assertEquals(1, $filter->getPage());
    }

    private function getRequest()
    {
        return new Request('http://test.local', 'GET', ['parameters' => ['page' => 5, 'search' => 'test', 'order' => 'asc', 'sort' => 'username']]);
    }
}