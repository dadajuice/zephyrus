<?php namespace Zephyrus\Tests\Database\Brokers;

use Zephyrus\Database\Brokers\ListBroker;
use Zephyrus\Database\Core\Database;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Tests\Database\DatabaseTestCase;

class ListBrokerTest extends DatabaseTestCase
{
    public function testBasicListView()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $list = $instance->inflate();
        $rows = $list->getRows();
        self::assertCount(6, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
        self::assertEquals(6, $list->getCount());
        self::assertEquals(6, $list->getTotalCount());
        self::assertEquals(1, $list->getCurrentPage());
        self::assertEquals("Batman", $list->getRow(1)->name);
    }

    public function testListViewWithSearchAndSort()
    {
        $request = new Request("http://example.com?search=man&sorts[force]=desc", "get", ['parameters' => [
            'search' => 'man',
            'sorts' => ['force' => 'desc']
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $list = $instance->inflate();

        $rows = $list->getRows();
        self::assertCount(4, $rows);
        self::assertEquals("Superman", $rows[0]->name);
        self::assertEquals(4, $list->getCount());
        self::assertEquals(6, $list->getTotalCount());
        self::assertEquals(1, $list->getCurrentPage());
        self::assertEquals("Super<mark>man</mark>", $list->mark("Superman"));
        self::assertEquals("Super<mark>man</mark> & Bat<mark>man</mark>", $list->mark("Superman & Batman"));
        self::assertEquals("dslkfjklsdf", $list->mark("dslkfjklsdf"));
        self::assertEquals("", $list->mark(""));
        self::assertEquals("", $list->mark(null));
        self::assertEquals("man", $list->getSearch());

        $list->setQueryFilter(null);
        self::assertEquals("Superman", $list->mark("Superman"));
    }

    public function testWithoutFilters()
    {
        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(6, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
    }

    public function testWithEqualsFilters()
    {
        $request = new Request("http://example.com?filters[name:equals]=Aquaman", "get", ['parameters' => [
            'filters' => ['name:equals' => 'Aquaman']
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(1, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
    }

    public function testWithContainsFilters()
    {
        $request = new Request("http://example.com?filters[name:contains]=man", "get", ['parameters' => [
            'filters' => ['name:contains' => 'man']
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(4, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
        self::assertEquals("Batman", $rows[1]->name);
    }

    public function testWithContainsFiltersAndSort()
    {
        $request = new Request("http://example.com?filters[name:contains]=man&sorts[force]=desc", "get", ['parameters' => [
            'filters' => ['name:contains' => 'man'],
            'sorts' => ['force' => 'desc']
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(4, $rows);
        self::assertEquals("Superman", $rows[0]->name);
    }

    public function testWithSearchAndSort()
    {
        $request = new Request("http://example.com?search=man&sorts[force]=desc", "get", ['parameters' => [
            'search' => 'man',
            'sorts' => ['force' => 'desc']
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(4, $rows);
        self::assertEquals("Superman", $rows[0]->name);
    }

    public function testWithSearch()
    {
        $request = new Request("http://example.com?search=diane", "get", ['parameters' => [
            'search' => 'diana'
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(1, $rows);
        self::assertEquals("Wonder Woman", $rows[0]->name);
    }

    public function testWithSearchNoResult()
    {
        $request = new Request("http://example.com?search=kjsdhfksjdhfkjhsddfhks", "get", ['parameters' => [
            'search' => 'kjsdhfksjdhfkjhsddfhks'
        ]]);
        RequestFactory::set($request);

        $instance = $this->buildListBroker($this->buildDatabase());
        $rows = $instance->findAllRows();
        self::assertCount(0, $rows);
    }

    private function buildListBroker(Database $database): ListBroker
    {
        return new class($database) extends ListBroker
        {
            public function configure()
            {
                $this->setAliasColumns(['force' => 'power']);
                $this->setSortAllowedColumns(['name', 'alter', 'force']);
                $this->setFilterAllowedColumns(['name']);
                $this->setSortDefaults(['name' => 'asc']);
                $this->setSearchableColumns(['name', 'alter']);
                $this->setPagerDefaultLimit(50); // Default
                $this->setPagerMaxLimit(50); // Default
                $this->setSortAscNullLast(true); // Default
                $this->setSortDescNullLast(false); // Default
            }

            public function findAllRows(): array
            {
                return $this->filteredSelect("SELECT * FROM heroes");
            }

            public function count(): \stdClass
            {
                $total = $this->selectSingle("SELECT COUNT(*) as n FROM heroes")->n;
                $current = $this->baseCount("SELECT COUNT(*) as n FROM heroes")->n;
                return (object) [
                    'current' => $current,
                    'total' => $total
                ];
            }
        };
    }
}
