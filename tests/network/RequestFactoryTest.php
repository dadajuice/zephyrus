<?php namespace Zephyrus\Tests;

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\RequestFactory;

class RequestFactoryTest extends TestCase
{
    public function testSimpleCapture()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_GET['test'] = 'yeah';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        self::assertEquals('yeah', $request->getParameter('test'));
    }

    public function testCapturePut()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/';
        $_POST['test'] = ["1", "2", "3"];
        $_POST['__method'] = 'put';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        self::assertEquals('PUT', $request->getMethod());
        self::assertEquals('2', $request->getParameter('test')[1]);
    }

    public function testCaptureDelete()
    {
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['REQUEST_URI'] = 'http://test.local/users/3';
        RequestFactory::set(null);
        $request = RequestFactory::read();
        self::assertEquals('DELETE', $request->getMethod());
    }
}