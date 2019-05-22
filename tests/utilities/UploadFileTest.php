<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\UploadException;
use Zephyrus\Utilities\Uploaders\UploadFile;

class UploadFileTest extends TestCase
{
    public function testProperties()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 825
        ];
        $file = new UploadFile($data);
        self::assertEquals(859, $file->getSize());
        self::assertEquals(ROOT_DIR . '/config.ini', $file->getTemporaryFilename());
        self::assertEquals('test.ini', $file->getOriginalFilename());
        self::assertEquals('text/plain', $file->getMimeType());
        self::assertEquals('ini', $file->getExtension());
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidUpload()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'name' => 'test.txt',
            'tmp_name' => '/tmp/' . md5(time()),
            'size' => 0
        ];
        touch($data['tmp_name']);
        $file = new UploadFile($data);
        @unlink($data['tmp_name']);
        $file->upload('/tmp/test.txt');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidData()
    {
        $data = [
            'type' => 'text/plain',
            'name' => 'test.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 7
     */
    public function testCantWriteError()
    {
        $data = [
            'error' => UPLOAD_ERR_CANT_WRITE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 1
     */
    public function testIniSizeError()
    {
        $data = [
            'error' => UPLOAD_ERR_INI_SIZE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 2
     */
    public function testFormSizeError()
    {
        $data = [
            'error' => UPLOAD_ERR_FORM_SIZE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 3
     */
    public function testPartialError()
    {
        $data = [
            'error' => UPLOAD_ERR_PARTIAL,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 4
     */
    public function testNoFileError()
    {
        $data = [
            'error' => UPLOAD_ERR_NO_FILE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 6
     */
    public function testNoTmpDirError()
    {
        $data = [
            'error' => UPLOAD_ERR_NO_TMP_DIR,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 8
     */
    public function testExtensionError()
    {
        $data = [
            'error' => UPLOAD_ERR_EXTENSION,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 900
     */
    public function testFileSizeError()
    {
        $data = [
            'error' => UploadException::ERR_FILE_SIZE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 902
     */
    public function testMimeTypeError()
    {
        $data = [
            'error' => UploadException::ERR_MIME_TYPE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 903
     */
    public function testDirectoryError()
    {
        $data = [
            'error' => UploadException::ERR_DIRECTORY_EXISTS,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 904
     */
    public function testWritableError()
    {
        $data = [
            'error' => UploadException::ERR_DIRECTORY_WRITABLE,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 901
     */
    public function testInvalidExtensionError()
    {
        $data = [
            'error' => UploadException::ERR_EXTENSION,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }
    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     * @expectedExceptionCode 666
     */
    public function testUnknownError()
    {
        $data = [
            'error' => 666,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 799
        ];
        new UploadFile($data);
    }
}