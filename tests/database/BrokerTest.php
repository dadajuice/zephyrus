<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Broker;
use Zephyrus\Database\Database;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class BrokerTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        $db = Database::getInstance();
        $db->query('DROP TABLE heroes;');
    }

    public function testConnection()
    {
        $db = Database::getInstance();
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        $db->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
        $db->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
        $db->query("INSERT INTO heroes(id, name) VALUES (3, '<b>Flash</b>');");
    }

    /**
     * @depends testConnection
     */
    public function testPager()
    {
        $class = new class() extends Broker {
            public function findAll()
            {
                return $this->selectAll("SELECT * FROM heroes");
            }
        };
        $req = new Request('http://test.local/3', 'GET', ['id' => '3']);
        RequestFactory::set($req);
        $pager = $class->buildPager(3, 1);
        $limit = $pager->getSqlLimit();
        self::assertEquals(" LIMIT 0, 1", $limit);
        $pager2 = $class->getPager();
        self::assertEquals(" LIMIT 0, 1", $pager2->getSqlLimit());
        $res = $class->findAll();
        self::assertEquals(1, count($res));
    }

    /**
     * @depends testConnection
     */
    public function testGetDatabase()
    {
        $class = new class() extends Broker {
            public function insert()
            {
                return $this->getDatabase()->getLastInsertedId();
            }
        };
        $id = $class->insert();
        self::assertEquals(3, $id);
    }

    /**
     * @depends testConnection
     */
    public function testFindById()
    {
        $class = new class() extends Broker {
            public function findById($id)
            {
                return $this->selectUnique("SELECT * FROM heroes WHERE id = ?", [$id]);
            }
        };
        $row = $class->findById(2);
        self::assertEquals('Superman', $row['name']);
    }

    /**
     * @depends testConnection
     */
    public function testFindByIdWithHtml()
    {
        $class = new class() extends Broker {
            public function findById($id)
            {
                return $this->selectUnique("SELECT * FROM heroes WHERE id = ?", [$id], "<b>");
            }
        };
        $row = $class->findById(3);
        self::assertEquals('<b>Flash</b>', $row['name']);
    }

    /**
     * @depends testConnection
     */
    public function testFindAll()
    {
        $class = new class() extends Broker {
            public function findAll()
            {
                return $this->selectAll("SELECT * FROM heroes");
            }
        };
        $row = $class->findAll();
        self::assertEquals(3, count($row));
    }

    /**
     * @depends testConnection
     */
    public function testFindAllWithHtml()
    {
        $class = new class() extends Broker {
            public function findAll()
            {
                return $this->selectAll("SELECT * FROM heroes", [], "<b>");
            }
        };
        $row = $class->findAll();
        self::assertEquals('<b>Flash</b>', $row[2]['name']);
    }

    /**
     * @depends testConnection
     */
    public function testTransaction()
    {
        $class = new class() extends Broker {
            public function insert()
            {
                $this->transaction(function () {
                    $this->query("INSERT INTO heroes(id, name) VALUES (8, 'Arrow');");
                });
            }

            public function findAll()
            {
                return $this->selectAll("SELECT * FROM heroes");
            }
        };
        $class->insert();
        $row = $class->findAll();
        self::assertEquals(4, count($row));
    }

    /**
     * @depends testConnection
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testInvalidTransaction()
    {
        $class = new class() extends Broker {
            public function insert()
            {
                $this->transaction(function ($database, $value) {

                });
            }

        };
        $class->insert();
    }

    /**
     * @depends testConnection
     */
    public function testTransactionWithDatabase()
    {
        $class = new class() extends Broker {
            public function insert()
            {
                return $this->transaction(function (Database $database) {
                    $this->query("INSERT INTO heroes(id, name) VALUES (5, 'Bob');");
                    return $database->getLastInsertedId();
                });
            }
        };
        $id = $class->insert();
        self::assertEquals(5, $id);
    }
}