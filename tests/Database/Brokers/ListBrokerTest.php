<?php namespace Zephyrus\Tests\Database\Brokers;

use Zephyrus\Database\Brokers\ListBroker;
use Zephyrus\Database\Core\Database;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Tests\Database\DatabaseTestCase;

class ListBrokerTest extends DatabaseTestCase
{
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
                $this->setPagerDefaultLimit(50); // Default
                $this->setPagerMaxLimit(50); // Default
                $this->setSortAscNullLast(true); // Default
                $this->setSortDescNullLast(false); // Default
            }

            public function findAllRows(): array
            {
                return $this->filteredSelect("SELECT * FROM heroes");
            }

            public function count(): int
            {
                return 0;
            }
        };
    }
}
