<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zephyrus\Application\Controller;
use Zephyrus\Exceptions\XmlParseException;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;

class ControllerXmlTest extends TestCase
{
    public function testXmlArray()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                return parent::xml(['test' => ['a' => '2', 'b' => 'bob', 3 => 't']], 'root');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(str_contains($output, '<root><test><a>2</a><b>bob</b><node3>t</node3></test></root>'));
    }

    public function testXmlString()
    {
        $controller = new class() extends Controller {

            public function index(): Response
            {
                return parent::xml("<toto><name>Bob Lewis</name></toto>");
            }
        };
        self::assertTrue(str_contains($controller->index()->getContent(), '<toto><name>Bob Lewis</name></toto>'));
    }

    public function testXmlException()
    {
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage("XML parsing failed with message [String could not be parsed as XML]. Consult the raw data for more information.");
        $controller = new class() extends Controller {

            public function index(): Response
            {
                return parent::xml("sfdg");
            }
        };
        $controller->index();
    }

    public function testXmlObject()
    {
        $controller = new class() extends Controller {

            public function index()
            {
                $xmlstr = "<?xml version='1.0' ?><movies><movie><title>The Dark Knight</title><year>2008</year></movie></movies>";
                $movies = new \SimpleXMLElement($xmlstr);
                return parent::xml($movies);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        self::assertTrue(str_contains($output, '<movies><movie><title>The Dark Knight</title><year>2008</year></movie></movies>'));
    }
}
