<?php namespace Zephyrus\Network\Request;

use PHPUnit\Framework\TestCase;
use stdClass;
use Zephyrus\Exceptions\JsonParseException;
use Zephyrus\Exceptions\XmlParseException;
use Zephyrus\Network\ContentType;

class RequestBodyTest extends TestCase
{
    public function testDefaultParameter()
    {
        $data = 'username=test&password=toto';
        $body = new RequestBody($data);
        $this->assertFalse($body->hasParameter("nope"));
        $this->assertNull($body->getParameter("nope"));
        $this->assertEquals("my_default", $body->getParameter("nope", "my_default"));
    }

    public function testHttpMethodOverride()
    {
        $body = new RequestBody("");
        $this->assertNull($body->getHttpMethodOverride());
        $body = new RequestBody("__method=delete");
        $this->assertEquals("DELETE", $body->getHttpMethodOverride()->value);
        $body = new RequestBody("__method=DELETE");
        $this->assertEquals("DELETE", $body->getHttpMethodOverride()->value);
    }

    public function testSimpleForm()
    {
        $data = 'username=test&password=toto';
        $body = new RequestBody($data);
        $this->assertTrue($body->hasParameter("username"));
        $this->assertTrue($body->hasParameter("password"));
        $this->assertEquals(ContentType::FORM, $body->getContentType());
        $this->assertEquals($data, $body->getRawData());
        $this->assertCount(2, $body->getParameters());
        $this->assertEquals([
            "username" => "test",
            "password" => "toto"
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
        $body->setParameters([
            'lastname' => 'wayne',
            'firstname' => 'bruce'
        ]);
        $this->assertCount(4, $body->getParameters());
        $this->assertEquals("wayne", $body->getParameter("lastname"));
    }

    public function testNestedForm()
    {
        $data = 'first=value&arr[]=foo+bar&arr[]=baz';
        $body = new RequestBody($data);
        $this->assertEquals([
            "first" => "value",
            "arr" => ["foo bar", "baz"]
        ], $body->getParameters());
        $this->assertEquals("foo bar", $body->getParameter("arr")[0]);
        $this->assertEquals("baz", $body->getParameter("arr")[1]);
    }

    public function testSimpleJson()
    {
        $data = '{"username": "test", "password": "toto"}';
        $body = new RequestBody($data, ContentType::JSON);
        $this->assertEquals([
            "username" => "test",
            "password" => "toto"
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
    }

    public function testNestedJson()
    {
        $data = '{"username": "test", "password": "toto", "contact": {"phone": "555-555-5555", "email": "toto@mail.com"}}';
        $body = new RequestBody($data, ContentType::JSON);
        $this->assertEquals([
            "username" => "test",
            "password" => "toto",
            "contact" => (object) ["phone" => "555-555-5555", "email" => "toto@mail.com"]
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
        $this->assertEquals("555-555-5555", $body->getParameter("contact")->phone);
        $this->assertEquals("toto@mail.com", $body->getParameter("contact")->email);
    }

    public function testNestedEmptyJson()
    {
        $data = '{"username": "test", "password": "toto", "contact": {}}';
        $body = new RequestBody($data, ContentType::JSON);
        $this->assertEquals([
            "username" => "test",
            "password" => "toto",
            "contact" => (object) []
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
        $this->assertEquals(new stdClass(), $body->getParameter("contact"));
    }

    public function testFailedJson()
    {
        $this->expectException(JsonParseException::class);
        $data = '{"username": "test", "password": "toto", "contact": {}';
        new RequestBody($data, ContentType::JSON);
    }

    public function testSimpleXml()
    {
        $data = '<test><username>test</username><password>toto</password></test>';
        $body = new RequestBody($data, ContentType::XML);
        $this->assertEquals([
            "username" => "test",
            "password" => "toto"
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
    }

    public function testNestedXml()
    {
        $data = '<test>
                    <username>test</username>
                    <password>toto</password>
                    <contact>
                        <phone>555-555-5555</phone>
                        <email>toto@mail.com</email>
                        <address>
                            <street>1428 Elm Street</street>
                            <city>Springwood</city>
                        </address>
                    </contact>
                 </test>';
        $body = new RequestBody($data, ContentType::XML_APP);
        $this->assertEquals([
            "username" => "test",
            "password" => "toto",
            "contact" => (object) [
                "phone" => "555-555-5555",
                "email" => "toto@mail.com",
                "address" => (object) [
                    "street" => "1428 Elm Street",
                    "city" => "Springwood"
                ]
            ]
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
        $this->assertEquals("555-555-5555", $body->getParameter("contact")->phone);
        $this->assertEquals("toto@mail.com", $body->getParameter("contact")->email);
        $this->assertEquals("Springwood", $body->getParameter("contact")->address->city);
        $this->assertEquals("1428 Elm Street", $body->getParameter("contact")->address->street);
    }

    public function testNestedEmptyXml()
    {
        $data = '<test>
                    <username>test</username>
                    <password>toto</password>
                    <contact></contact>
                 </test>';
        $body = new RequestBody($data, ContentType::XML_APP);
        $this->assertEquals([
            "username" => "test",
            "password" => "toto",
            "contact" => (object) []
        ], $body->getParameters());
        $this->assertEquals("test", $body->getParameter("username"));
        $this->assertEquals("toto", $body->getParameter("password"));
        $this->assertEquals(new stdClass(), $body->getParameter("contact"));
    }

    public function testFailedXml()
    {
        $this->expectException(XmlParseException::class);
        $data = '<test>
                    <username>test</user>
                    <password>toto</password>
                    <contact></contact>
                 </test>';
        new RequestBody($data, ContentType::XML_APP);
    }
}
