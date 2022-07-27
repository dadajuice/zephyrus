<?php //namespace Zephyrus\Tests\database;
//
//use PHPUnit\Framework\TestCase;
//use Zephyrus\Database\Core\Database;
//use Zephyrus\Database\Core\Filterable;
//use Zephyrus\Database\DatabaseFactory;
//use Zephyrus\Network\Request;
//use Zephyrus\Network\RequestFactory;
//
//class BrokerFilterableTest extends TestCase
//{
//    use Filterable;
//
//    public function testFilterQuerySearchAndSortSimple()
//    {
//        $sql = "SELECT * FROM clients";
//        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man', 'order' => 'asc', 'sort' => 'alias']]);
//        RequestFactory::set($r);
//        $this->setSearchableFields(['name', 'email']);
//        $this->applyFilter();
//        $response = $this->filterQuery($sql);
//        self::assertEquals("SELECT * FROM clients WHERE ((name LIKE '%man%') OR (email LIKE '%man%')) ORDER BY alias asc", $response);
//    }
//
//    public function testFilterQuerySearch()
//    {
//        $sql = "SELECT * FROM clients";
//        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
//        RequestFactory::set($r);
//        $this->setSearchableFields(['name', 'email']);
//        $this->applyFilter();
//        $response = $this->filterQuery($sql);
//        self::assertEquals("SELECT * FROM clients WHERE ((name LIKE '%man%') OR (email LIKE '%man%'))", $response);
//    }
//
//    public function testFilterQuerySearchWithInnerQuery()
//    {
//        $sql = "SELECT name, email, (SELECT CONCAT(firstname, ' ', lastname) FROM authentication) as fullname FROM clients";
//        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
//        RequestFactory::set($r);
//        $this->setSearchableFields(['name', 'email']);
//        $this->applyFilter();
//        $response = $this->filterQuery($sql);
//        self::assertEquals("SELECT name, email, (SELECT CONCAT(firstname, ' ', lastname) FROM authentication) as fullname FROM clients WHERE ((name LIKE '%man%') OR (email LIKE '%man%'))", $response);
//    }
//
//    public function testFilterQuerySearchWithHavingWithoutGroupBy()
//    {
//        $sql = "SELECT name FROM heroes HAVING MAX(epic)";
//        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
//        RequestFactory::set($r);
//        $this->setSearchableFields(['name', 'email']);
//        $this->applyFilter();
//        $response = $this->filterQuery($sql);
//        self::assertEquals("SELECT name FROM heroes WHERE ((name LIKE '%man%') OR (email LIKE '%man%')) HAVING MAX(epic)", $response);
//    }
//
//    public function testFilterQuerySearchWithHavingWithGroupBy()
//    {
//        $sql = "SELECT count(id) n FROM heroes GROUP BY name HAVING n = 2";
//        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
//        RequestFactory::set($r);
//        $this->setSearchableFields(['name', 'email']);
//        $this->applyFilter();
//        $response = $this->filterQuery($sql);
//        self::assertEquals("SELECT count(id) n FROM heroes WHERE ((name LIKE '%man%') OR (email LIKE '%man%')) GROUP BY name HAVING n = 2", $response);
//    }
//
//    public function testFilterQuerySearchWithInnerQueryWithWhere()
//    {
//        $sql = "SELECT name, email, (SELECT CONCAT(firstname, ' ', lastname) FROM authentication WHERE id > 4) as fullname FROM clients";
//        $r = new Request('http://test.local', 'GET', ['parameters' => ['search' => 'man']]);
//        RequestFactory::set($r);
//        $this->setSearchableFields(['name', 'email']);
//        $this->applyFilter();
//        $response = $this->filterQuery($sql);
//        self::assertEquals("SELECT name, email, (SELECT CONCAT(firstname, ' ', lastname) FROM authentication WHERE id > 4) as fullname FROM clients WHERE ((name LIKE '%man%') OR (email LIKE '%man%'))", $response);
//    }
//
//    public function getDatabase(): Database
//    {
//        return DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
//    }
//}
