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
        $req = new Request('http://test.local/', 'GET', [], [], $files);
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
        $req = new Request('http://test.local/', 'GET', [], [], $files);
        RequestFactory::set($req);
        $uploader = new Uploader('upload');
        self::assertEquals(1, $uploader->count());
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