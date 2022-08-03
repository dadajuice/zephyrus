<?php namespace Zephyrus\Tests\Database\Brokers;

use Zephyrus\Database\Brokers\SqlResult;
use Zephyrus\Tests\Database\DatabaseTestCase;

class SqlResultTest extends DatabaseTestCase
{
    public function testToArray()
    {
        $db = $this->buildDatabase();
        $statement = $db->query("SELECT * FROM heroes");
        $result = new SqlResult($statement);
        $rows = $result->toArray();
        self::assertEquals("Wonder Woman", $rows[3]->name);
        self::assertEquals("Flash", $rows[4]->name);
    }

    public function testStream()
    {
        $db = $this->buildDatabase();
        $statement = $db->query("SELECT * FROM heroes");
        $result = new SqlResult($statement);
        $result->stream(function ($row) {
            self::assertEquals("Batman", $row->name);
            return false;
        });
        self::assertTrue(true);
    }

    public function testChunks()
    {
        $db = $this->buildDatabase();
        $statement = $db->query("SELECT * FROM heroes");
        $result = new SqlResult($statement);
        $result->chunks(function ($rows) {
            self::assertCount(2, $rows);
            self::assertEquals("Batman", $rows[0]->name);
            self::assertEquals("Superman", $rows[1]->name);
            return false;
        }, 2);
        self::assertTrue(true);
    }
}
