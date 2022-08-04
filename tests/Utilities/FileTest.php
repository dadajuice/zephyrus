<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\FileSystem\Directory;
use Zephyrus\Utilities\FileSystem\File;

class FileTest extends TestCase
{
    public function testEncryption()
    {
        $file = File::create(ROOT_DIR . '/lib/filesystem/to_encrypt.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/to_encrypt.txt'));

        $file->write("my secret");
        self::assertEquals("my secret", $file->read());

        $file->encrypt('my_key');
        self::assertNotEquals("my secret", $file->read());

        $file->decrypt("my_key");
        self::assertEquals("my secret", $file->read());

        unlink(ROOT_DIR . '/lib/filesystem/to_encrypt.txt');
    }

    public function testEncryptionCopy()
    {
        $file = File::create(ROOT_DIR . '/lib/filesystem/to_encrypt.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/to_encrypt.txt'));

        $file->write("my secret");
        self::assertEquals("my secret", $file->read());

        $file->encrypt('my_key', ROOT_DIR . '/lib/filesystem/to_encrypt2.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/to_encrypt2.txt'));

        self::assertEquals("my secret", $file->read());
        $file = new File(ROOT_DIR . '/lib/filesystem/to_encrypt2.txt');
        self::assertNotEquals("my secret", $file->read());
        $file->decrypt("my_key", ROOT_DIR . '/lib/filesystem/to_encrypt3.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/to_encrypt3.txt'));
        $file = new File(ROOT_DIR . '/lib/filesystem/to_encrypt3.txt');
        self::assertEquals("my secret", $file->read());

        unlink(ROOT_DIR . '/lib/filesystem/to_encrypt.txt');
        unlink(ROOT_DIR . '/lib/filesystem/to_encrypt2.txt');
        unlink(ROOT_DIR . '/lib/filesystem/to_encrypt3.txt');
    }

    public function testCreate()
    {
        $file = File::create(ROOT_DIR . '/lib/filesystem/newly.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/newly.txt'));
        self::assertEquals('newly', $file->getFilename());
        self::assertEquals('newly.txt', $file->getBasename());
        self::assertEquals('txt', $file->getExtension());
        self::assertEquals(ROOT_DIR . '/lib/filesystem', $file->getDirectoryName());
        self::assertEquals(date(FORMAT_DATE), date(FORMAT_DATE, $file->getLastModifiedTime()));
        self::assertEquals(date(FORMAT_DATE), date(FORMAT_DATE, $file->getLastAccessedTime()));
        self::assertFalse(strpos($file->getRealPath(), '..'));
    }

    /**
     * @depends testCreate
     */
    public function testCreateFailed()
    {
        $this->expectException(\InvalidArgumentException::class);
        File::create(ROOT_DIR . '/lib/filesystem/newly.txt');
    }

    public function testCreateOverride()
    {
        $file = File::create(ROOT_DIR . '/lib/filesystem/newly999.txt');
        $file->write("yes");
        $file = File::create(ROOT_DIR . '/lib/filesystem/newly999.txt', true);
        $file->write("no");
        $file = new File(ROOT_DIR . '/lib/filesystem/newly999.txt');
        self::assertEquals("no", $file->read());
        $file->remove();
    }

    /**
     * @depends testCreate
     */
    public function testWrite()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/newly.txt');
        $file->write('Batman');
        $content = $file->read();
        self::assertEquals("Batman", $content);
        self::assertEquals('text/plain', $file->getMimeType());

        $file->append(' rocks');
        $content = $file->read();
        self::assertEquals("Batman rocks", $content);

        $file->write('Got overwritten');
        $content = $file->read();
        self::assertEquals("Got overwritten", $content);
    }

    /**
     * @depends testWrite
     */
    public function testCopy()
    {
        self::assertTrue(File::exists(ROOT_DIR . '/lib/filesystem/newly.txt'));
        $file = new File(ROOT_DIR . '/lib/filesystem/newly.txt');
        $file->copy(ROOT_DIR . '/lib/filesystem/test/newly_bk.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/test/newly_bk.txt'));
        $file = new File(ROOT_DIR . '/lib/filesystem/test/newly_bk.txt');
        self::assertEquals("Got overwritten", $file->read());
        $file->remove();
        $directory = new Directory(ROOT_DIR . '/lib/filesystem/test');
        $directory->remove();
        self::assertFalse(file_exists(ROOT_DIR . '/lib/filesystem/test'));
    }

    /**
     * @depends testCopy
     */
    public function testTouch()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/newly.txt');
        $file->touch(strtotime('2025-01-01 15:00:00'));
        self::assertEquals('2025-01-01', date(FORMAT_DATE, $file->getLastModifiedTime()));
    }

    /**
     * @depends testTouch
     */
    public function testMove()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/newly.txt');
        $file->move(ROOT_DIR . '/lib/filesystem/test_subdirectory/newly2.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/test_subdirectory/newly2.txt'));
    }

    /**
     * @depends testMove
     */
    public function testRename()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/test_subdirectory/newly2.txt');
        $file->rename('newly3.txt');
        self::assertTrue(file_exists(ROOT_DIR . '/lib/filesystem/test_subdirectory/newly3.txt'));
    }

    /**
     * @depends testRename
     */
    public function testRemove()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/test_subdirectory/newly3.txt');
        $file->remove();
        self::assertFalse(file_exists(ROOT_DIR . '/lib/filesystem/test_subdirectory/newly3.txt'));
    }

    public function testRead()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        $content = $file->read();
        self::assertEquals("One ring to rule them all", $content);
        self::assertEquals(25, $file->size());
    }

    public function testOutput()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        ob_start();
        $file->output();
        self::assertEquals("One ring to rule them all", ob_get_clean());
    }

    public function testMd5()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        $hash = $file->md5();
        self::assertEquals("bc713027e780c5d0a8d452b3df9f58dc", $hash);
    }

    public function testSha1()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        $hash = $file->sha1();
        self::assertEquals("a0f04b70b90227f205b9106a9ee1d440d5942d11", $hash);
    }

    public function testCurlFile()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');

        $curlFile = $file->buildCurlFile();
        self::assertEquals("existing.txt", $curlFile->postname);

        $curlFile = $file->buildCurlFile("bob.log");
        self::assertEquals("bob.log", $curlFile->postname);

        $curlFile = $file->buildCurlFile("bob");
        self::assertEquals("bob.txt", $curlFile->postname);
    }

    public function testInvalidPath()
    {
        $this->expectException(\InvalidArgumentException::class);
        new File(ROOT_DIR . '/lib/filesystem/lksdfkjdshfksdf.txt');
    }

    public function testGetDirectoryInstance()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        $directory = $file->getDirectory();
        self::assertEquals(ROOT_DIR . '/lib/filesystem', $directory->getPath());
    }

    public function testGetHandle()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        $handle = $file->getHandle('r');
        self::assertTrue(is_resource($handle));
        fclose($handle);
    }

    public function testParent()
    {
        $file = new File(ROOT_DIR . '/lib/filesystem/existing.txt');
        self::assertEquals(ROOT_DIR . '/lib', $file->parent(2));
    }
}
