<?php namespace Zephyrus\Tests\Network;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    public function testInstance()
    {
        $response = ResponseFactory::getInstance()->plain("hello world");
        self::assertEquals("hello world", $response->getContent());
    }

    public function testInvalidDownload()
    {
        $this->expectException(\InvalidArgumentException::class);
        ResponseFactory::getInstance()->download("non-present-file.bob");
    }
}
