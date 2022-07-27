<?php //namespace Zephyrus\Tests;
//
//use PHPUnit\Framework\TestCase;
//use Zephyrus\Database\Core\Database;
//use Zephyrus\Database\DatabaseFactory;
//
//class DatabaseStatementTest extends TestCase
//{
//    /**
//     * @var Database
//     */
//    private static $database;
//
//    public static function setUpBeforeClass(): void
//    {
//        self::$database = DatabaseFactory::buildFromConfigurations(['dbms' => 'sqlite']);
//        self::$database->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT, power REAL DEFAULT 0);');
//        self::$database->query("INSERT INTO heroes(id, name, power) VALUES (1, 'Batman', 3.5);");
//    }
//
//    public function testHtmlSanitize()
//    {
//        self::$database->query("INSERT INTO heroes(id, name) VALUES (2, '<p>superman</p>');");
//        $result = self::$database->query("SELECT * FROM heroes WHERE id = 2");
//        $result->setSanitizeCallback(function ($value) {
//            return strip_tags($value);
//        });
//        $row = $result->next();
//        self::assertEquals('superman', $row->name);
//    }
//}