<?php namespace Zephyrus\Network\Request;

use PHPUnit\Framework\TestCase;

class RequestAcceptTest extends TestCase
{
    public function testStandardPriority()
    {
        // With space support
        $header = "text/html, application/xhtml+xml, application/xml;q=0.9, */*;q=0.8";
        $accept = new RequestAccept($header);
        $this->assertEquals($header, $accept->getAccept());
        $this->assertEquals([
            "text/html",
            "application/xhtml+xml",
            "application/xml",
            "*/*"
        ], $accept->getAcceptedContentTypes());
    }

    public function testSpecificTypePriority()
    {
        // Specific type priority (those without * should be first for same priority)
        $header = "*/*,text/html,application/*,application/xml";
        $accept = new RequestAccept($header);
        $this->assertEquals($header, $accept->getAccept());
        $this->assertEquals([
            "text/html",
            "application/xml",
            "application/*",
            "*/*"
        ], $accept->getAcceptedContentTypes());
    }

    public function testMixedTypePriority()
    {
        // Specific type priority (those without * should be first for same priority)
        $header = "*/*;q=0.9,text/html;q=0.4,application/json;q=0.5";
        $accept = new RequestAccept($header);
        $this->assertEquals($header, $accept->getAccept());
        $this->assertEquals([
            "*/*",
            "application/json",
            "text/html"
        ], $accept->getAcceptedContentTypes());
    }
}
