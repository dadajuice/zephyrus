<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\FileSystem\Directory;
use Zephyrus\Utilities\FileSystem\File;

class DirectoryTest extends TestCase
{
    public function testCreate()
    {
        $directory = Directory::create(ROOT_DIR . '/lib/filesystem/new_dir');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/new_dir'));
        self::assertEquals(date(FORMAT_DATE), date(FORMAT_DATE, $directory->getLastModifiedTime()));
        Directory::create(ROOT_DIR . '/lib/filesystem/new_dir', 0777, true);
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/new_dir'));
    }

    /**
     * @depends testCreate
     */
    public function testInvalidCreate()
    {
        $this->expectException(\InvalidArgumentException::class);
        Directory::create(ROOT_DIR . '/lib/filesystem/new_dir');
    }

    public function testInvalidConstruct()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory/bob.dat');
    }

    public function testListFiles()
    {
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory');
        self::assertEquals(15, $directory->size());
        $fileNames = $directory->getFilenames();
        self::assertEquals(3, count($fileNames));
        self::assertTrue(in_array('martin.dat', $fileNames));
        self::assertTrue(in_array('bob.dat', $fileNames));
        self::assertTrue(in_array('batman.cfg', $fileNames));
    }

    public function testGetFiles()
    {
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory');
        $files = $directory->getFiles();
        self::assertEquals(3, count($files));
        self::assertEquals("Bob", $files[0]->read());
    }

    public function testFind()
    {
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory');
        $filenames = $directory->findFilenames('.*\.cfg');
        self::assertEquals(1, count($filenames));
        self::assertEquals("batman.cfg", $filenames[0]);
    }

    public function testFindInstance()
    {
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory');
        $filenames = $directory->findFiles('.*\.cfg');
        self::assertEquals(1, count($filenames));
        self::assertEquals("Config", $filenames[0]->read());
    }

    public function testScan()
    {
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory');
        $count = 0;
        $directory->scan(function ($element) use (&$count) {
            if (!is_dir($element)) {
                $count++;
            }
        });
        self::assertEquals(3, $count);
    }

    public function testCopy()
    {
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test_subdirectory');
        $directory->copy(ROOT_DIR . '/lib/filesystem/bootleg');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/bootleg'));
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/bootleg');
        $directory->remove();
    }

    /**
     * @depends testCreate
     */
    public function testRemove()
    {
        Directory::create(ROOT_DIR . '/lib/filesystem/new_dir/dead_inside');
        $file = File::create(ROOT_DIR . '/lib/filesystem/new_dir/test.dat');
        $file->write("hello world");
        $file->touch(strtotime('2025-01-01 15:00:00'));
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/new_dir');
        self::assertEquals('2025-01-01', date(FORMAT_DATE, $directory->getLastModifiedTime()));
        $directory->remove();
        self::assertFalse(file_exists(ROOT_DIR . '/lib/filesystem/new_dir'));
    }
}
