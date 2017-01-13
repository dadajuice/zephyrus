<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

class ResponseTest extends TestCase
{
    public function testHeader()
    {
        $response = new Response();
        $response->sendContentType(ContentType::CSS);
        $response->sendHeader('test', '1234');
        $response->sendResponseCode();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('Content-Type: text/css;charset=UTF-8', $headers));
        self::assertTrue(in_array('test:1234', $headers));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAlreadySent()
    {
        $response = new Response();
        $response->sendResponseCode();
        $response->sendResponseCode();
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testAlreadySent2()
    {
        $response = new Response();
        $response->sendContentType();
        $response->sendContentType();
    }
}