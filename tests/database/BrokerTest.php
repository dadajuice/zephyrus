<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Broker;
use Zephyrus\Database\Database;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;

class BrokerTest extends TestCase
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
        self::$database->query("INSERT INTO heroes(id, name) VALUES (3, '<b>Flash</b>');");
    }

    public function testPager()
    {
        $class = new class(self::$database) extends Broker {
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

    public function testSetDatabase()
    {
        $class = new class() extends Broker {

            public function insert()
            {
                parent::setDatabase(new Database('sqlite::memory:'));
                return $this->getDatabase()->getLastInsertedId();
            }
        };
        $id = $class->insert();
        self::assertEquals(0, $id);
    }

    public function testGetDatabase()
    {
        $class = new class(self::$database) extends Broker {
            public function insert()
            {
                return $this->getDatabase()->getLastInsertedId();
            }
        };
        $id = $class->insert();
        self::assertEquals(3, $id);
    }

    public function testFindById()
    {
        $class = new class(self::$database) extends Broker {
            public function findById($id)
            {
                return $this->selectUnique("SELECT * FROM heroes WHERE id = ?", [$id]);
            }
        };
        $row = $class->findById(2);
        self::assertEquals('Superman', $row['name']);
    }

    public function testFindByIdWithHtml()
    {
        $class = new class(self::$database) extends Broker {
            public function findById($id)
            {
                return $this->selectUnique("SELECT * FROM heroes WHERE id = ?", [$id], "<b>");
            }
        };
        $row = $class->findById(3);
        self::assertEquals('<b>Flash</b>', $row['name']);
    }

    public function testFindAll()
    {
        $class = new class(self::$database) extends Broker {
            public function findAll()
            {
                return $this->selectAll("SELECT * FROM heroes");
            }
        };
        $row = $class->findAll();
        self::assertEquals(3, count($row));
    }

    public function testFindAllWithHtml()
    {
        $class = new class(self::$database) extends Broker {
            public function findAll()
            {
                return $this->selectAll("SELECT * FROM heroes", [], "<b>");
            }
        };
        $row = $class->findAll();
        self::assertEquals('<b>Flash</b>', $row[2]['name']);
    }

    public function testTransaction()
    {
        $class = new class(self::$database) extends Broker {
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
     * @expectedException \Zephyrus\Exceptions\DatabaseException
     */
    public function testInvalidTransaction()
    {
        $class = new class(self::$database) extends Broker {
            public function insert()
            {
                $this->transaction(function ($database, $value) {

                });
            }

        };
        $class->insert();
    }

    public function testTransactionWithDatabase()
    {
        $class = new class(self::$database) extends Broker {
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