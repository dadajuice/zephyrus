<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Database;

class SharedDatabaseTest extends TestCase
{
    public function testSameDatabase()
    {
        $database = Database::buildFromConfiguration();
        $database2 = Database::buildFromConfiguration();
        self::assertEquals($database->getDataSourceName(), $database2->getDataSourceName());
    }
}