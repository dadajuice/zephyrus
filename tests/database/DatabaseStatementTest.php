<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Database;

class DatabaseStatementTest extends TestCase
{
    public function testConnection()
    {
        $db = Database::getInstance();
        $db->query('CREATE TABLE heroes(id NUMERIC PRIMARY KEY, name TEXT);');
        $db->query("INSERT INTO heroes(id, name) VALUES (1, 'Batman');");
    }

    public static function tearDownAfterClass()
    {
        $db = Database::getInstance();
        $db->query('DROP TABLE heroes;');
    }

    /**
     * @depends testConnection
     */
    public function testHtmlSanitize()
    {
        $db = Database::getInstance();
        $db->query("INSERT INTO heroes(id, name) VALUES (2, '<p>superman</p>');");
        $result = $db->query("SELECT * FROM heroes WHERE id = 2");
        $row = $result->next();
        self::assertEquals('superman', $row['name']);
    }

    /**
     * @depends testConnection
     */
    public function testAllowHtml()
    {
        $db = Database::getInstance();
        $db->query("INSERT INTO heroes(id, name) VALUES (3, '<b>arrow</b>');");
        $result = $db->query("SELECT * FROM heroes WHERE id = 3");
        $result->addAllowedHtmlTag('<b>');
        self::assertTrue($result->isDirectHtmlTagsAllowed());
        self::assertEquals('<b>', $result->getAllowedHtmlTags());
        $result->setAllowedHtmlTags('<b><u>');
        $row = $result->next();
        self::assertEquals('<b>arrow</b>', $row['name']);
        $result = $db->query("SELECT * FROM heroes WHERE id = 3");
        $result->disallowDirectHtmlTags();
        $row = $result->next();
        self::assertEquals('arrow', $row['name']);
    }
}