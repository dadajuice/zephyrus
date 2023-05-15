<?php namespace Zephyrus\Tests\Network;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\RequestFactory;

class RequestFactoryTest extends TestCase
{
    public function testSimpleCapture()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::PLAIN;
        $_GET['test'] = 'yeah';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        self::assertEquals('yeah', $request->getParameter('test'));
    }

    public function testRequestedUri()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local:445';
        $_SERVER['SERVER_PORT'] = '445';
        $_SERVER['CONTENT_TYPE'] = ContentType::PLAIN;
        $_GET['test'] = 'yeah';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        self::assertEquals('http://test.local:445/test', $request->getRequestedUri());
    }

    public function testJsonRawCapture()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::JSON;
        RequestFactory::set(null);
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', '{"username": "test", "password": "toto"}');
        $request = RequestFactory::read();
        stream_wrapper_restore("php");
        self::assertEquals('test', $request->getParameter('username'));
        self::assertEquals('toto', $request->getParameter('password'));
    }

    public function testJsonNestedRawCapture()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::JSON;
        RequestFactory::set(null);
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', '{"username": "test", "password": "toto", "contact": {"phone": "555-555-5555", "email": "toto@mail.com"}}');
        $request = RequestFactory::read();
        stream_wrapper_restore("php");
        self::assertEquals('test', $request->getParameter('username'));
        self::assertEquals('toto', $request->getParameter('password'));
        self::assertEquals('toto@mail.com', $request->getParameter('contact')->email);
    }

    public function testJsonNestedEmptyRawCapture()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::JSON;
        RequestFactory::set(null);
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', '{"username": "test", "password": "toto", "contact": {}}');
        $request = RequestFactory::read();
        stream_wrapper_restore("php");
        self::assertEquals('test', $request->getParameter('username'));
        self::assertEquals('toto', $request->getParameter('password'));
        self::assertEquals(new \stdClass(), $request->getParameter('contact'));
    }

    public function testXmlRawCapture()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::XML_APP;
        RequestFactory::set(null);
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', '<test><username>test2</username><password>toto2</password></test>');
        $request = RequestFactory::read();
        stream_wrapper_restore("php");
        self::assertEquals('test2', $request->getParameter('username'));
        self::assertEquals('toto2', $request->getParameter('password'));
    }

    public function testCapturePut()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::PLAIN;
        $_POST['test'] = ["1", "2", "3"]; // Will add [] automatically ...
        $_POST['test2'] = ["4", "5", "6"];
        $_POST['__method'] = 'put';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        print_r($request->getParameters());
        self::assertEquals('PUT', $request->getMethod());
        self::assertEquals('2', $request->getParameter('test[]')[1]);
        self::assertEquals('5', $request->getParameter('test2[]')[1]);
    }

    public function testCaptureDelete()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/users/3';
        $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
        $_SERVER['HTTP_HOST'] = 'test.local';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['CONTENT_TYPE'] = ContentType::PLAIN;
        $_POST['__method'] = 'delete';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        self::assertEquals('DELETE', $request->getMethod());
    }
}
