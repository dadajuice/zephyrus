<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

class ResponseTest extends TestCase
{
    public function testHeader()
    {
        $response = new Response();
        $response->setContentType(ContentType::CSS);
        $response->addHeader('test', '1234');
        $response->setCharset("TEST");
        $response->send();
        $headers = xdebug_get_headers();
        self::assertEquals(200, $response->getCode());
        self::assertTrue(in_array('Content-Type: text/css;charset=TEST', $headers));
        self::assertTrue(in_array('test:1234', $headers));
        self::assertEquals("1234", $response->getHeaders()['test']);
    }

    public function testHeaders()
    {
        $response = new Response();
        $response->setContentType(ContentType::HTML);
        $response->addHeaders([
            'test' => '1234',
            'test2' => '12345'
        ]);
        $response->send();
        $headers = xdebug_get_headers();
        self::assertTrue(in_array('test:1234', $headers));
        self::assertTrue(in_array('test2:12345', $headers));
    }

    public function testContent()
    {
        $response = new Response();
        $response->setContentType(ContentType::HTML);
        $response->setContent("test");
        self::assertEquals(ContentType::HTML, $response->getContentType());
        self::assertEquals("test", $response->getContent());
        self::assertEquals("UTF-8", $response->getCharset());
    }

    public function testTrueHtmlContent()
    {
        $response = new Response();
        $response->setContentType(ContentType::HTML);
        $response->setContent("<html>test</html>");
        self::assertTrue($response->hasHtmlContent());
    }
}
