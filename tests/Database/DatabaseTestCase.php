<?php namespace Zephyrus\Tests\Database;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Database;

abstract class DatabaseTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $db = new Database(Configuration::getDatabase());
        $db->query("DROP TABLE IF EXISTS heroes CASCADE");
        $db->query('CREATE TABLE heroes(id SERIAL PRIMARY KEY, name TEXT NULL, alter TEXT NULL, power FLOAT)');
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Batman', 'Bruce Wayne', 20.56);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Superman', 'Clark Kent', 50.30);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Aquaman', 'Arthur Curry', 23.50);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Wonder Woman', 'Diana Prince', 12.67);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Flash', 'Barry Allan', 5.89);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Green Lantern', 'Hal Jordan', 12.12);");
    }

    public static function tearDownAfterClass(): void
    {
        $db = new Database(Configuration::getDatabase());
        $db->query("DROP TABLE heroes CASCADE");
    }

    public function rebootDatabase(Database $db)
    {
        $db->query("DROP TABLE IF EXISTS heroes CASCADE");
        $db->query('CREATE TABLE heroes(id SERIAL PRIMARY KEY, name TEXT NULL, alter TEXT NULL, power FLOAT)');
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Batman', 'Bruce Wayne', 20.56);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Superman', 'Clark Kent', 50.30);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Aquaman', 'Arthur Curry', 23.50);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Wonder Woman', 'Diana Prince', 12.67);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Flash', 'Barry Allan', 5.89);");
        $db->query("INSERT INTO heroes(name, alter, power) VALUES ('Green Lantern', 'Hal Jordan', 12.12);");
    }

    public function buildDatabase(): Database
    {
        return new Database(Configuration::getDatabase());
    }
}
