<?php namespace Zephyrus\Tests\Application\Rules;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Rule;

class FileRulesTest extends TestCase
{
    public function testIsUpload()
    {
        $rule = Rule::fileUpload();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertTrue($rule->isValid($file));
        $file = [
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
        ];
        self::assertFalse($rule->isValid($file));
        $file = [
            'error' => UPLOAD_ERR_CANT_WRITE,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));
        self::assertFalse($rule->isValid([]));
    }

    public function testIsMimeTypeAllowed()
    {
        $rule = Rule::fileMimeType();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertTrue($rule->isValid($file));

        $rule = Rule::fileMimeType("", ["text/html"]);
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));
        self::assertFalse($rule->isValid([]));
    }

    public function testIsImageMimeTypeAllowed()
    {
        $rule = Rule::imageMimeType();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertTrue($rule->isValid($file));

        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'text/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/init.php',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));
        self::assertFalse($rule->isValid([]));
    }

    public function testIsExtensionAllowed()
    {
        $rule = Rule::fileExtension();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertTrue($rule->isValid($file));
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.docx',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));

        $rule = Rule::fileExtension("", ["gif"]);
        $file = [
            'error' => UPLOAD_ERR_CANT_WRITE,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));
        self::assertFalse($rule->isValid([]));
    }

    public function testIsFileSizeCompliant()
    {
        $rule = Rule::fileSize();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertTrue($rule->isValid($file));

        $rule = Rule::fileSize("", 0.0001);
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/jpg',
            'name' => 'batlike.jpg',
            'tmp_name' => ROOT_DIR . '/lib/images/batlike.jpg',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));
        self::assertFalse($rule->isValid([]));
    }

    public function testIsImageAuthentic()
    {
        $rule = Rule::imageAuthentic();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'working.png',
            'tmp_name' => ROOT_DIR . '/lib/images/working.png',
            'size' => 1300000
        ];
        self::assertTrue($rule->isValid($file));
        self::assertFalse($rule->isValid([]));
    }

    public function testIsImageNotAuthentic()
    {
        $rule = Rule::imageAuthentic();
        $file = [
            'error' => UPLOAD_ERR_OK,
            'type' => 'image/png',
            'name' => 'not_image.png',
            'tmp_name' => ROOT_DIR . '/lib/images/not_image.png',
            'size' => 1300000
        ];
        self::assertFalse($rule->isValid($file));
    }
}
