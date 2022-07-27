<?php //namespace Zephyrus\Tests;
//
//use PHPUnit\Framework\TestCase;
//use Zephyrus\Database\Core\Database;
//use Zephyrus\Database\DatabaseBroker;
//use Zephyrus\Database\DatabaseFactory;
//use Zephyrus\Exceptions\DatabaseException;
//use Zephyrus\Network\Request;
//use Zephyrus\Network\RequestFactory;
//
//class BrokerTest extends TestCase
//{
//    /**
//     * @var Database
//     */
//    private static $database;
//
//    public static function setUpBeforeClass(): void
//    {
//        self::$database = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
//        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
//        self::$database->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
//        self::$database->query("INSERT INTO heroes(id, name) VALUES (2, 'Superman');");
//        self::$database->query("INSERT INTO heroes(id, name) VALUES (3, '<b>Flash</b>');");
//    }
//
//    public function testPager()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function findAll()
//            {
//                return $this->filteredSelect("SELECT * FROM heroes");
//            }
//        };
//        $req = new Request('http://test.local/3', 'GET', ['id' => '3']);
//        RequestFactory::set($req);
//        $class->configurePager(1, 'page');
//        $class->applyPager(3);
//        $limit = $class->getPager()->getSqlLimitClause(self::$database->getAdapter());
//        self::assertEquals(" LIMIT 0, 1", $limit);
//        $pager2 = $class->getPager();
//        self::assertEquals(" LIMIT 0, 1", $pager2->getSqlLimitClause(self::$database->getAdapter()));
//        $res = $class->findAll();
//        self::assertEquals(1, count($res));
//    }
//
//    public function testSetDatabase()
//    {
//        $class = new class() extends DatabaseBroker
//        {
//            public function insert()
//            {
//                parent::setDatabase(DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']));
//                return $this->getDatabase()->getLastInsertedId();
//            }
//        };
//        $id = $class->insert();
//        self::assertEquals(0, $id);
//    }
//
//    public function testGetDatabase()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function insert()
//            {
//                return $this->getDatabase()->getLastInsertedId();
//            }
//        };
//        $id = $class->insert();
//        self::assertEquals(3, $id);
//    }
//
//    public function testFindById()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function findById($id)
//            {
//                return $this->selectSingle("SELECT * FROM heroes WHERE id = ?", [$id]);
//            }
//        };
//        $row = $class->findById(2);
//        self::assertEquals('Superman', $row->name);
//    }
//
//    public function testFindByIdWithSanitizeHtml()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function findById($id)
//            {
//                $this->setSanitizeCallback(function ($value) {
//                    return strip_tags($value);
//                });
//                return $this->selectSingle("SELECT * FROM heroes WHERE id = ?", [$id]);
//            }
//        };
//        $row = $class->findById(3);
//        self::assertEquals('Flash', $row->name);
//    }
//
//    public function testFindAll()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function findAll()
//            {
//                $this->addSessionVariable('test', 'test');
//                return $this->select("SELECT * FROM heroes");
//            }
//        };
//        $rows = $class->findAll();
//        self::assertEquals(3, count($rows));
//        self::assertEquals('Batman', $rows[0]->name);
//    }
//
//    public function testFindAllWithSanitizeHtml()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function findAll()
//            {
//                $this->setSanitizeCallback(function ($value) {
//                    return htmlspecialchars($value, ENT_NOQUOTES);
//                });
//                return $this->select("SELECT * FROM heroes", []);
//            }
//        };
//        $rows = $class->findAll();
//        self::assertEquals('&lt;b&gt;Flash&lt;/b&gt;', $rows[2]->name);
//    }
//
//    public function testTransaction()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function insert()
//            {
//                $this->transaction(function () {
//                    $this->query("INSERT INTO heroes(id, name) VALUES (8, 'Arrow');");
//                });
//            }
//
//            public function findAll()
//            {
//                return $this->select("SELECT * FROM heroes");
//            }
//        };
//        $class->insert();
//        $rows = $class->findAll();
//        self::assertEquals(4, count($rows));
//    }
//
//    public function testInvalidTransaction()
//    {
//        $this->expectException(DatabaseException::class);
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function insert()
//            {
//                $this->transaction(function ($database, $value) {
//
//                });
//            }
//
//        };
//        $class->insert();
//    }
//
//    public function testTransactionWithDatabase()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function insert()
//            {
//                return $this->transaction(function (Database $database) {
//                    $this->removePager();
//                    $this->query("INSERT INTO heroes(id, name) VALUES (5, 'Bob');");
//                    return $database->getLastInsertedId();
//                });
//            }
//        };
//        $id = $class->insert();
//        self::assertEquals(5, $id);
//    }
//
//    public function testNullResults()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function findById($id)
//            {
//                $this->query("INSERT INTO heroes(id, name) VALUES (10, null);");
//                return $this->selectSingle("SELECT name FROM heroes WHERE id = $id");
//            }
//        };
//        $row = $class->findById(10);
//        self::assertEquals(null, $row->name);
//    }
//
//    public function testEncryption()
//    {
//        $class = new class(self::$database) extends DatabaseBroker
//        {
//            public function __construct(?Database $database = null)
//            {
//                parent::__construct($database);
//                $this->setEncryptedFields(['name']);
//            }
//
//            public function insert($name)
//            {
//                $this->query("INSERT INTO heroes(id, name) VALUES (99, ?);", [
//                    $this->sensitize($name)
//                ]);
//            }
//
//            public function findById($id)
//            {
//                return $this->selectSingle("SELECT name FROM heroes WHERE id = $id");
//            }
//        };
//        $class->insert("Martin Sandwich");
//
//        $statement = self::$database->query("SELECT name FROM heroes WHERE id = 99");
//        self::assertNotEquals("Martin Sandwich", $statement->next()->name);
//
//        $row = $class->findById(99);
//        self::assertEquals("Martin Sandwich", $row->name);
//        self::assertEquals("name", $class->getEncryptedFields()[0]);
//    }
//}