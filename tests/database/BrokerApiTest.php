<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Broker;
use Zephyrus\Database\BrokerApi;
use Zephyrus\Database\Database;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class BrokerApiTest extends TestCase
{
    /**
     * @var Database
     */
    private static $database;

    public static function setUpBeforeClass()
    {
        self::$database = new Database('sqlite::memory:');
        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        self::$database->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        self::$database->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
    }

    public function testFindById()
    {
        $class = new class(self::$database) extends BrokerApi {
            public function findById($id)
            {
                return $this->selectSingle("SELECT * FROM heroes WHERE id = ?", [$id]);
            }
        };
        $result = $class->findById(2);
        self::assertEquals('Superman', $result->name);
    }

    public function testFindAll()
    {
        $class = new class(self::$database) extends BrokerApi {
            public function findAll()
            {
                return $this->select("SELECT * FROM heroes");
            }
        };
        $results = $class->findAll();
        self::assertEquals(2, count($results));
        self::assertEquals('Superman', $results[1]->name);
    }
}
