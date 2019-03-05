<?php

use PHPUnit\Framework\TestCase;
use Zephyrus\Network\Request;

class RequestTest extends TestCase
{
    public function testParameters()
    {
        $uri = 'http://127.0.0.1/test/3?sort=4&filter[]=a&filter[]=b#section';
        $method = 'GET';
        $parameters = ['id' => '3', 'sort' => '4', 'filter' => ['a', 'b']];
        $request = new Request($uri, $method, [
            'parameters' => $parameters
        ]);

        self::assertEquals('4', $request->getParameter('sort'));
        self::assertEquals(null, $request->getParameter('dfghd'));
        self::assertEquals('def', $request->getParameter('dfghd', 'def'));
        $readParameters = $request->getParameters();
        self::assertEquals('4', $readParameters['sort']);

        $request->prependParameter('added1', 'success1');
        $request->addParameter('added', 'success');
        self::assertEquals('success1', $request->getParameter('added1'));
        self::assertEquals('success', $request->getParameter('added'));
    }

    public function testServer()
    {
        $server = [];
        $uri = 'http://127.0.0.1/test/3?sort=4&filter[]=a&filter[]=b#section';
        $method = 'GET';
        $server['REMOTE_ADDR'] = '192.168.2.1';
        $server['HTTP_ACCEPT'] = 'text/html';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $request = new Request($uri, $method, [
            'server' => $server
        ]);
        self::assertEquals('192.168.2.1', $request->getClientIp());
        self::assertEquals('chrome', $request->getUserAgent());
        self::assertEquals('text/html', $request->getAccept());
    }

    public function testHeader()
    {
        $server = [];
        $uri = 'http://127.0.0.1/test/3?sort=4&filter[]=a&filter[]=b#section';
        $method = 'GET';
        $server['REMOTE_ADDR'] = '192.168.2.1';
        $server['HTTP_ACCEPT'] = 'text/html';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $request = new Request($uri, $method, [
            'server' => $server
        ]);
        self::assertEquals([], $request->getHeaders());
        self::assertEquals(null, $request->getHeader('TEST'));
    }

    public function testReferer()
    {
        $server = [];
        $uri = 'http://127.0.0.1/test/3';
        $method = 'GET';
        $server['REMOTE_ADDR'] = '192.168.2.1';
        $server['HTTP_ACCEPT'] = 'text/html';
        $server['HTTP_USER_AGENT'] = 'chrome';
        $server['HTTP_REFERER'] = "http://127.0.0.1/test/2";
        $request = new Request($uri, $method, [
            'server' => $server
        ]);
        self::assertEquals(4, $request->getServerVariable('INVALID', '4'));
        self::assertEquals(4, count($request->getServerVariables()));
        self::assertEquals("chrome", $request->getServerVariable('HTTP_USER_AGENT'));
        self::assertEquals("http://127.0.0.1/test/2", $request->getReferer());
    }

    public function testCookies()
    {
        $uri = 'http://127.0.0.1/test';
        $method = 'GET';
        $cookies = ['test' => 'value'];
        $request = new Request($uri, $method, [
            'cookies' => $cookies
        ]);
        self::assertEquals('value', $request->getCookieValue('test'));
        self::assertEquals(null, $request->getCookieValue('rtytr'));
        self::assertEquals('def', $request->getCookieValue('rtytr', 'def'));
        self::assertTrue($request->hasCookie('test'));
        self::assertFalse($request->hasCookie('dsfghgg'));
        $readCookies = $request->getCookies();
        self::assertTrue(isset($readCookies['test']));
    }

    public function testFiles()
    {
        $uri = 'http://127.0.0.1/test';
        $method = 'GET';
        $files = ['test' => 'value'];
        $request = new Request($uri, $method, [
            'files' => $files
        ]);
        self::assertEquals('value', $request->getFile('test'));
        self::assertEquals(null, $request->getFile('rtytr'));
        $readFiles = $request->getFiles();
        self::assertTrue(isset($readFiles['test']));
    }

    public function testUri()
    {
        $uri = 'http://bob:omega123@127.0.0.1/test/3?sort=4&filter[]=a&filter[]=b#section';
        $method = 'GET';
        $request = new Request($uri, $method);

        self::assertEquals('127.0.0.1', $request->getUri()->getHost());
        self::assertEquals('http://127.0.0.1', $request->getBaseUrl());
        self::assertEquals('GET', $request->getMethod());
        self::assertEquals('sort=4&filter[]=a&filter[]=b', $request->getUri()->getQuery());
        self::assertEquals('http://bob:omega123@127.0.0.1/test/3?sort=4&filter[]=a&filter[]=b#section', $request->getRequestedUri());
        self::assertEquals('http', $request->getUri()->getScheme());
        self::assertEquals('section', $request->getUri()->getFragment());
        self::assertEquals('/test/3', $request->getUri()->getPath());
        self::assertEquals('bob', $request->getUri()->getUsername());
        self::assertEquals('omega123', $request->getUri()->getPassword());
        self::assertFalse($request->getUri()->isSecure());
    }
}