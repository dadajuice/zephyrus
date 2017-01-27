<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Uploaders\FileUploader;
use Zephyrus\Utilities\Uploaders\UploadFile;

class FileUploaderTest extends TestCase
{
    public function testProperties()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->addAllowedExtensions(['txt', 'sql']);
        $uploader->addAllowedExtension('db');
        $uploader->addAllowedMimeType('text/plain');
        $uploader->addAllowedMimeTypes(['text/html', 'text/xml']);
        $uploader->setMaxSize(4);
        $uploader->setOverwritePermitted(true);
        $uploader->setKeepOriginalFilename(false);
        $uploader->setDestinationDirectory(ROOT_DIR . '/tmp');
        $uploader->setDestinationFilename('bob');
        self::assertTrue(in_array('txt', $uploader->getAllowedExtensions()));
        self::assertTrue(in_array('text/html', $uploader->getAllowedMimeTypes()));
        self::assertEquals(4, $uploader->getMaxSize());
        self::assertTrue($uploader->isOverwritePermitted());
        self::assertEquals(ROOT_DIR . '/tmp/', $uploader->getDestinationDirectory());
        self::assertEquals('bob.ini', $uploader->getDestinationFilename());
        self::assertEquals(ROOT_DIR . '/tmp/bob.ini', $uploader->getDestinationTarget());
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testInvalidExtensionUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->addAllowedExtensions(['txt', 'sql']);
        $uploader->addAllowedExtension('db');
        $uploader->addAllowedMimeType('text/plain');
        $uploader->addAllowedMimeTypes(['text/html', 'text/xml']);
        $uploader->setMaxSize(4);
        $uploader->setDestinationDirectory('tmp');
        $uploader->upload();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testInvalidDirectoryUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->setDestinationDirectory(ROOT_DIR . '/dsfg');
        $uploader->upload('config.ini');
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidOverrideUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->setOverwritePermitted(false);
        $uploader->setDestinationDirectory(ROOT_DIR);
        $uploader->upload('config.ini');
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testInvalidWritableUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->setDestinationDirectory('/etc');
        $uploader->setKeepOriginalFilename(true);
        $uploader->upload();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testInvalidMimeUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->addAllowedMimeType('text/html');
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        $uploader->upload();
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testInvalidSizeUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->setMaxSize(1);
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        $uploader->upload();
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidUpload()
    {
        $file = $this->buildUploadFile();
        $uploader = new FileUploader($file);
        $uploader->setMaxSize(4);
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        $uploader->upload();
    }

    private function buildUploadFile()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 1300000
        ];
        return new UploadFile($data);
    }
}