<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\DatabaseFactory;

class SharedDatabaseTest extends TestCase
{
    public function testSameDatabase()
    {
        $database = DatabaseFactory::buildFromConfigurations();
        $database2 = DatabaseFactory::buildFromConfigurations();
        self::assertEquals($database->getAdapter()->getDatabaseManagementSystem(),
            $database2->getAdapter()->getDatabaseManagementSystem());
    }
}