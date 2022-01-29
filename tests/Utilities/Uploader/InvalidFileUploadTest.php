<?php namespace Zephyrus\Tests\Utilities\Uploader;

use PHPUnit\Framework\TestCase;
use Zephyrus\Exceptions\UploaderException;
use Zephyrus\Utilities\Uploader\FileUpload;

class InvalidFileUploadTest extends TestCase
{
    public function testInvalidUploadKeyStructure()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_INVALID_STRUCTURE);
        new FileUpload(['tmp_name' => '/test', 'size' => 3]);
    }

    public function testInvalidUploadTypeStructure()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_INVALID_STRUCTURE);
        new FileUpload([
            'error' => [0, 0],
            'tmp_name' => ['/test', '/test2'],
            'size' => [3, 4],
            'name' => ['test', 'test2'],
            'type' => ['image/gif', 'image/png']
        ]);
    }

    public function testInvalidUploadTmpFile()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UploaderException::ERROR_UNREADABLE_TMP_FILE);
        new FileUpload([
            'error' => 0,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorIniSize()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_INI_SIZE);
        new FileUpload([
            'error' => UPLOAD_ERR_INI_SIZE,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorFormSize()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_FORM_SIZE);
        new FileUpload([
            'error' => UPLOAD_ERR_FORM_SIZE,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorPartial()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_PARTIAL);
        new FileUpload([
            'error' => UPLOAD_ERR_PARTIAL,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorNoFile()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_NO_FILE);
        new FileUpload([
            'error' => UPLOAD_ERR_NO_FILE,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorNoTmpDir()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_NO_TMP_DIR);
        new FileUpload([
            'error' => UPLOAD_ERR_NO_TMP_DIR,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorWrite()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_CANT_WRITE);
        new FileUpload([
            'error' => UPLOAD_ERR_CANT_WRITE,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorExtension()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionCode(UPLOAD_ERR_EXTENSION);
        new FileUpload([
            'error' => UPLOAD_ERR_EXTENSION,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }

    public function testInvalidUploadErrorUnknown()
    {
        self::expectException(UploaderException::class);
        self::expectExceptionMessage("Unknown upload error.");
        new FileUpload([
            'error' => 342,
            'tmp_name' => '/khsdfkhsdf',
            'size' => 3,
            'name' => 'test',
            'type' => 'image/gif'
        ]);
    }
}
