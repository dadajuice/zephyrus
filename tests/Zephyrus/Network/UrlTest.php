<?php namespace Zephyrus\Network;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testSimpleUrl()
    {
        $url = new Url("http://localhost/testing");
        $this->assertEquals("http://localhost/testing", $url->getRawUrl());
        $this->assertEquals("http://localhost", $url->getBaseUrl());
        $this->assertEquals("localhost", $url->getHost());
        $this->assertEquals("http", $url->getScheme());
        $this->assertEquals(80, $url->getPort());
        $this->assertEquals("/testing", $url->getPath());
    }

    public function testCompleteUrl()
    {
        $url = new Url("https://localhost/products/10?sort=id&order=asc#header");
        $this->assertEquals("https://localhost/products/10?sort=id&order=asc#header", $url->getRawUrl());
        $this->assertEquals("https://localhost", $url->getBaseUrl());
        $this->assertEquals("localhost", $url->getHost());
        $this->assertEquals("https", $url->getScheme());
        $this->assertEquals(443, $url->getPort());
        $this->assertEquals("/products/10", $url->getPath());
        $this->assertEquals("header", $url->getFragment());
        $this->assertEquals("sort=id&order=asc", $url->getQuery());
        $queryString = $url->buildQueryString();
        $this->assertEquals("id", $queryString->getArgument('sort'));
        $this->assertEquals("asc", $queryString->getArgument('order'));
        $this->assertEquals([
            'sort' => 'id',
            'order' => 'asc'
        ], $queryString->getArguments());
    }

    public function testPathWithArrays()
    {
        $url = new Url("https://localhost/products?filters[]=name&filters[]=city");
        $this->assertEquals("https://localhost/products?filters[]=name&filters[]=city", $url->getRawUrl());
        $this->assertEquals("https://localhost", $url->getBaseUrl());
        $this->assertEquals("/products", $url->getPath());
        $queryString = $url->buildQueryString();
        $this->assertEquals([
            'name', 'city'
        ], $queryString->getArgument('filters'));
        $this->assertEquals([
            'filters' => ['name', 'city']
        ], $queryString->getArguments());
    }

    public function testPathWithAuth()
    {
        $url = new Url("https://bwayne:Omega123@www.example.com:8080/products");
        $this->assertEquals("https://bwayne:Omega123@www.example.com:8080/products", $url->getRawUrl());
        $this->assertEquals("https://www.example.com:8080", $url->getBaseUrl());
        $this->assertEquals("/products", $url->getPath());
        $this->assertEquals("www.example.com", $url->getHost());
        $this->assertEquals("https", $url->getScheme());
        $this->assertEquals(8080, $url->getPort());
        $this->assertEquals("bwayne", $url->getUsername());
        $this->assertEquals("Omega123", $url->getPassword());
        $this->assertTrue($url->isSecure());
    }
}
