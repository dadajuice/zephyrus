<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Utilities\Uploaders\ImageUploader;
use Zephyrus\Utilities\Uploaders\UploadFile;

class ImageUploaderTest extends TestCase
{
    public function testDefaultConfiguration()
    {
        $file = $this->buildWorkingUploadFile();
        $uploader = new ImageUploader($file);
        self::assertTrue(in_array('gif', $uploader->getAllowedExtensions()));
        self::assertTrue(in_array('image/gif', $uploader->getAllowedMimeTypes()));
        self::assertFalse($uploader->isForcingImageRebuild());
        $uploader->setForcingImageRebuild(true);
        self::assertTrue($uploader->isForcingImageRebuild());
    }

    public function testValidPngImage()
    {
        $file = $this->buildWorkingUploadFile();
        $uploader = new ImageUploader($file);
        $uploader->setForcingImageRebuild(true);
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        try {
            $uploader->upload();
            self::assertEquals("1", "2");
        } catch (\Exception $e) {
            self::assertEquals("Upload failed", $e->getMessage());
        }
    }

    public function testValidJpegImage()
    {
        $file = $this->buildWorkingUploadJpegFile();
        $uploader = new ImageUploader($file);
        $uploader->setForcingImageRebuild(true);
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        try {
            $uploader->upload();
            self::assertEquals("1", "2");
        } catch (\Exception $e) {
            self::assertEquals("Upload failed", $e->getMessage());
        }
    }

    public function testValidGifImage()
    {
        $file = $this->buildWorkingUploadGifFile();
        $uploader = new ImageUploader($file);
        $uploader->setForcingImageRebuild(true);
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        try {
            $uploader->upload();
            self::assertEquals("1", "2");
        } catch (\Exception $e) {
            self::assertEquals("Upload failed", $e->getMessage());
        }
    }

    /**
     * @expectedException \Exception
     */
    public function testInvalidImage()
    {
        $file = $this->buildInvalidUploadFile();
        $uploader = new ImageUploader($file);
        $uploader->setDestinationDirectory(ROOT_DIR . '/lib');
        $uploader->upload();
    }

    private function buildWorkingUploadFile()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        return new UploadFile($data);
    }

    private function buildWorkingUploadJpegFile()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/jpeg',
            'name' => 'batlike.jpg',
            'tmp_name' => ROOT_DIR . '/lib/images/batlike.jpg',
            'size' => 1300000
        ];
        return new UploadFile($data);
    }

    private function buildInvalidUploadFile()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'invalid.png',
            'tmp_name' => ROOT_DIR . '/lib/images/invalid.png',
            'size' => 1300000
        ];
        return new UploadFile($data);
    }

    private function buildWorkingUploadGifFile()
    {
        $data = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/gif',
            'name' => 'Injection.gif',
            'tmp_name' => ROOT_DIR . '/lib/images/dance.gif',
            'size' => 1300000
        ];
        return new UploadFile($data);
    }
}