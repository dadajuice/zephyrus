<?php namespace Zephyrus\Tests\Database\Core;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Core\Database;
use Zephyrus\Database\Core\DatabaseSource;

class DatabaseStatementTest extends TestCase
{
    public function testHtmlSanitize()
    {
        $db = new Database(new DatabaseSource());
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT NULL, enabled INTEGER, power REAL);');
        $db->query("INSERT INTO heroes(id, name, enabled, power) VALUES (1, 'Batman', 1, 5.6);");

        $db->query("INSERT INTO heroes(id, name) VALUES (2, '<p>superman</p>');");
        $result = $db->query("SELECT * FROM heroes WHERE id = 2");
        $result->setSanitizeCallback(function ($value) {
            return strip_tags($value);
        });
        $row = $result->next();
        self::assertEquals('superman', $row->name);
    }

    public function testEmptyResultSet()
    {
        $db = new Database(new DatabaseSource());
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT NULL, enabled INTEGER, power REAL);');
        $db->query("INSERT INTO heroes(id, name, enabled, power) VALUES (1, 'Batman', 1, 5.6);");
        $result = $db->query("SELECT power FROM heroes WHERE id = 99");
        self::assertEquals("SELECT power FROM heroes WHERE id = 99", $result->getPdoStatement()->queryString);
        self::assertNull($result->next());
    }
}
