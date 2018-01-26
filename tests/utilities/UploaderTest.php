<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Utilities\Uploaders\Uploader;

class UploaderTest extends TestCase
{
    public function testMultipleFiles()
    {
        $files['upload'] = $this->buildUploadFile();
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        $uploader = new Uploader('upload');
        $uploader->setFileType(Uploader::TYPE_FILE);
        self::assertEquals(Uploader::TYPE_FILE, $uploader->getFileType());
        self::assertEquals(2, $uploader->count());
        $uploads = $uploader->getFiles();
        self::assertEquals(2, count($uploads));
    }

    public function testSingleFile()
    {
        $files['upload'] = $this->buildUploadSingleFile();
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        $uploader = new Uploader('upload');
        self::assertEquals(1, $uploader->count());
    }

    public function testSingleImageFile()
    {
        $files['upload'] = $this->buildUploadSingleFile();
        $files['upload']['type'] = 'image/png';
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        $uploader = new Uploader('upload');
        self::assertEquals(1, $uploader->count());
    }

    public function testMultipleImageFiles()
    {
        $files['upload'] = $this->buildUploadFile();
        $files['upload']['type'][0] = "image/png";
        $files['upload']['type'][1] = "image/png";
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        $uploader = new Uploader('upload');
        $uploader->setFileType(Uploader::TYPE_IMAGE);
        self::assertEquals(Uploader::TYPE_IMAGE, $uploader->getFileType());
        self::assertEquals(2, $uploader->count());
        $uploads = $uploader->getFiles();
        self::assertEquals(2, count($uploads));
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testSingleFileError()
    {
        $files['upload'] = $this->buildUploadSingleFile();
        $files['upload']['error'] = UPLOAD_ERR_NO_TMP_DIR;
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        new Uploader('upload');
    }

    /**
     * @expectedException \Zephyrus\Exceptions\UploadException
     */
    public function testMultipleFilesError()
    {
        $files['upload'] = $this->buildUploadFile();
        $files['upload']['error'][0] = UPLOAD_ERR_CANT_WRITE;
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        new Uploader('upload');
    }

    /**
     * @expectedException \Exception
     */
    public function testNameError()
    {
        $files['upload'] = $this->buildUploadSingleFile();
        $req = new Request('http://test.local/', 'GET', [
            'files' => $files
        ]);
        RequestFactory::set($req);
        new Uploader('invalid');
    }

    private function buildUploadFile()
    {
        $data = [
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'type' => ['text/plain', 'text/html'],
            'name' => ['test.ini', 'test.html'],
            'tmp_name' => [ROOT_DIR . '/config.ini', ROOT_DIR . '/config.ini'],
            'size' => [1300000, 1300000]
        ];
        return $data;
    }

    private function buildUploadSingleFile()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/plain',
            'name' => 'test.ini',
            'tmp_name' => ROOT_DIR . '/config.ini',
            'size' => 1300000
        ];
        return $data;
    }
}