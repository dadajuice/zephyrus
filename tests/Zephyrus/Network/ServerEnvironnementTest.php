<?php namespace Zephyrus\Network;

use PHPUnit\Framework\TestCase;
use stdClass;
use Zephyrus\Tests\RequestUtility;

class ServerEnvironnementTest extends TestCase
{
    public function testSimpleCapture()
    {
        $server['REQUEST_METHOD'] = 'GET';
        $server['REMOTE_ADDR'] = '127.0.0.1';
        $server['REQUEST_URI'] = '/test?id=yeah';
        $server['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $server['HTTP_HOST'] = 'test.local';
        $server['SERVER_PORT'] = '80';
        $server['CONTENT_TYPE'] = ContentType::PLAIN;

        $env = new ServerEnvironnement($server);
        $req = new Request($env);
        $this->assertEquals('yeah', $req->getParameter('id'));
        $this->assertEquals(80, $req->getServerEnvironnement()->getServerPort());
        $this->assertEquals('HTTP/1.1', $req->getServerEnvironnement()->getProtocol());
        $this->assertEquals('test.local', $req->getServerEnvironnement()->getHostname());
        $this->assertEquals('text/plain', $req->getServerEnvironnement()->getContentType());
        $this->assertEquals('127.0.0.1', $req->getServerEnvironnement()->getClientIp());
        $this->assertEquals('http://test.local/test?id=yeah', $req->getServerEnvironnement()->getRequestedUrl());
    }

    public function testJsonRawCapture()
    {
        $request = RequestUtility::postJson("/", '{"username": "test", "password": "toto"}');
        self::assertEquals('test', $request->getParameter('username'));
        self::assertEquals('toto', $request->getParameter('password'));
    }

    public function testJsonNestedRawCapture()
    {
        $request = RequestUtility::postJson("/", '{"username": "test", "password": "toto", "contact": {"phone": "555-555-5555", "email": "toto@mail.com"}}');
        self::assertEquals('test', $request->getParameter('username'));
        self::assertEquals('toto', $request->getParameter('password'));
        self::assertEquals('toto@mail.com', $request->getParameter('contact')->email);
    }

    public function testJsonNestedEmptyRawCapture()
    {
        $request = RequestUtility::postJson("/", '{"username": "test", "password": "toto", "contact": {}}');
        self::assertEquals('test', $request->getParameter('username'));
        self::assertEquals('toto', $request->getParameter('password'));
        self::assertEquals(new stdClass(), $request->getParameter('contact'));
    }

    public function testXmlRawCapture()
    {
        $request = RequestUtility::postXml("/", '<test><username>test2</username><password>toto2</password></test>');
        self::assertEquals('test2', $request->getParameter('username'));
        self::assertEquals('toto2', $request->getParameter('password'));
    }

    public function testCapturePut()
    {
        $request = RequestUtility::post("/", '__method=put&test[]=1&test[]=2&test[]=3&test2[]=4&test2[]=5&test2[]=6');
        self::assertEquals(HttpMethod::PUT, $request->getMethod());
        self::assertEquals('2', $request->getParameter('test')[1]);
        self::assertEquals('5', $request->getParameter('test2')[1]);
    }

    public function testCaptureDelete()
    {
        $request = RequestUtility::post("/users/3", '__method=delete');
        self::assertEquals(HttpMethod::DELETE, $request->getMethod());
    }
}
