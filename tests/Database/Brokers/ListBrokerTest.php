<?php namespace Zephyrus\Tests\Database\Brokers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Brokers\ListBroker;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseConfiguration;
use Zephyrus\Exceptions\FatalDatabaseException;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class ListBrokerTest extends TestCase
{
    public function testWithoutFilters()
    {
        $db = $this->initializeDatabase();

        $request = new Request("http://example.com", "get", ['parameters' => []]);
        RequestFactory::set($request);

        $instance = new class($db) extends ListBroker
        {
            public function configure()
            {
                $this->setAliasColumns(['amount' => 'price']);
                $this->setSortAllowedColumns(['name', 'brand', 'price']);
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

        $rows = $instance->findAllRows();
        self::assertCount(6, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
    }

    public function testWithEqualsFilters()
    {
        $db = $this->initializeDatabase();

        $request = new Request("http://example.com?filters[name:equals]=Aquaman", "get", ['parameters' => [
            'filters' => ['name:equals' => 'Aquaman']
        ]]);
        RequestFactory::set($request);

        $instance = new class($db) extends ListBroker
        {
            public function configure()
            {
                $this->setAliasColumns(['amount' => 'price']);
                $this->setSortAllowedColumns(['name', 'brand', 'price']);
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

        $rows = $instance->findAllRows();
        self::assertCount(1, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
    }

    public function testWithContainsFilters()
    {
        $db = $this->initializeDatabase();

        $request = new Request("http://example.com?filters[name:sensible-contains]=man", "get", ['parameters' => [
            'filters' => ['name:sensible-contains' => 'man']
        ]]);
        RequestFactory::set($request);

        $instance = new class($db) extends ListBroker
        {
            public function configure()
            {
                $this->setAliasColumns(['amount' => 'price']);
                $this->setSortAllowedColumns(['name', 'brand', 'price']);
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

        $rows = $instance->findAllRows();
        self::assertCount(4, $rows);
        self::assertEquals("Aquaman", $rows[0]->name);
        self::assertEquals("Batman", $rows[1]->name);
    }

    /**
     * Since the database is in memory, it will be destroyed if the instance changes.
     *
     * @return Database
     * @throws FatalDatabaseException
     */
    private function initializeDatabase(): Database
    {
        $db = new Database(new DatabaseConfiguration());
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT NULL, brand TEXT NULL, price DECIMAL);');
        $db->query("INSERT INTO heroes(id, name, brand, price) VALUES (1, 'Batman', 'DC', 20.56);");
        $db->query("INSERT INTO heroes(id, name, brand, price) VALUES (2, 'Ironman', 'Marvel', 10.10);");
        $db->query("INSERT INTO heroes(id, name, brand, price) VALUES (3, 'Aquaman', 'DC', 23.50);");
        $db->query("INSERT INTO heroes(id, name, brand, price) VALUES (4, 'Wonder Woman', 'DC', 12.67);");
        $db->query("INSERT INTO heroes(id, name, brand, price) VALUES (5, 'Captain America', 'Marvel', 5.89);");
        $db->query("INSERT INTO heroes(id, name, brand, price) VALUES (6, 'Green Arrow', 'DC', 12.12);");
        return $db;
    }
}
