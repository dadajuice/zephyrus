<?php namespace Zephyrus\Tests\Utilities\Components;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Utilities\Components\FilterConfiguration;
use Zephyrus\Utilities\Components\ListFilter;

class FilterConfigurationTest extends TestCase
{
    public function testCustomConfig()
    {
        $request = new Request("https://example.com?filtro[name:contains]=bob&pogo=8&limito=200&searcho=bob2&sorto[name]=asc", "get", ['parameters' => [
            'filtro' => ['name:contains' => 'bob'],
            'pogo' => 8,
            'limito' => 200,
            'searcho' => 'bob2',
            'sorto' => ['name' => 'asc']
        ]]);
        $configuration = new FilterConfiguration($request);
        $configuration->setFunnelParameters("filtro", "searcho");
        $configuration->setPaginationParameters("pogo", "limito");
        $configuration->setSortParameter("sorto");
        $filter = new ListFilter($configuration);
        $filter->getPagination()->setDefaultLimit(50, 200);

        self::assertEquals(['name:contains' => 'bob'], $filter->getFunnel()->getFilters());
        self::assertEquals(8, $filter->getPagination()->getCurrentPage());
        self::assertEquals(200, $filter->getPagination()->getLimit());
        self::assertEquals(['name' => 'asc'], $filter->getSort()->getSorts());
        self::assertEquals("bob2", $filter->getFunnel()->getSearch());
    }

    public function testDefaultConfig()
    {
        $request = new Request("https://example.com?filters[name:contains]=bob&page=8&limit=200&search=bob2&sorts[name]=asc", "get", ['parameters' => [
            'filters' => ['name:contains' => 'bob'],
            'page' => 8,
            'limit' => 200,
            'search' => 'bob2',
            'sorts' => ['name' => 'asc']
        ]]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);
        $filter->getPagination()->setDefaultLimit(50, 200);

        self::assertEquals(['name:contains' => 'bob'], $filter->getFunnel()->getFilters());
        self::assertEquals(8, $filter->getPagination()->getCurrentPage());
        self::assertEquals(200, $filter->getPagination()->getLimit());
        self::assertEquals(['name' => 'asc'], $filter->getSort()->getSorts());
        self::assertEquals("bob2", $filter->getFunnel()->getSearch());
    }

    public function testEmpty()
    {
        $request = new Request("https://example.com", "get", ['parameters' => []]);
        $configuration = new FilterConfiguration($request);
        $filter = new ListFilter($configuration);

        self::assertEmpty($filter->getFunnel()->getFilters());
        self::assertEquals(1, $filter->getPagination()->getCurrentPage());
        self::assertEquals(50, $filter->getPagination()->getLimit());
        self::assertEquals([], $filter->getSort()->getSorts());
        self::assertNull($filter->getFunnel()->getSearch());
    }
}
