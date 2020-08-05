<?php namespace Zephyrus\Tests\network;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ResponseFactory;

class ResponseFactoryTest extends TestCase
{
    public function testInstance()
    {
        $response = ResponseFactory::getInstance()->plain("hello world");
        self::assertEquals("hello world", $response->getContent());
    }
}
