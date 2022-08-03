<?php namespace Zephyrus\Tests\Database\Core;

use Zephyrus\Tests\Database\DatabaseTestCase;

class DatabaseStatementTest extends DatabaseTestCase
{
    public function testHtmlSanitize()
    {
        $db = $this->buildDatabase();
        $db->query("INSERT INTO heroes(id, name, alter, power) VALUES (7, '<p>Nightwing</p>', 'Dick Grayson', 9.2);");
        $result = $db->query("SELECT * FROM heroes WHERE id = 7");
        $result->setSanitizeCallback(function ($value) {
            return strip_tags($value);
        });
        $row = $result->next();
        self::assertEquals('Nightwing', $row->name);
    }

    public function testEmptyResultSet()
    {
        $db = $this->buildDatabase();
        $result = $db->query("SELECT power FROM heroes WHERE id = 99");
        self::assertEquals("SELECT power FROM heroes WHERE id = 99", $result->getPdoStatement()->queryString);
        self::assertNull($result->next());
    }
}
