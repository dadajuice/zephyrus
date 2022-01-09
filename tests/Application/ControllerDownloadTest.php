<?php namespace Zephyrus\Tests\Application;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Router;
use Zephyrus\Utilities\FileSystem\File;

class ControllerDownloadTest extends TestCase
{
    public function testDownload()
    {
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::download(ROOT_DIR . '/config.ini');
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        $headers = xdebug_get_headers();
        self::assertTrue(strpos($output, "[application]") !== false);
        self::assertTrue(in_array('Content-Disposition:attachment; filename="config.ini"', $headers));
    }

    public function testDownloadAndDelete()
    {
        $f = File::create(ROOT_DIR . '/lib/to_delete.txt');
        $f->write("Bubu");
        self::assertTrue(file_exists(ROOT_DIR . '/lib/to_delete.txt'));
        $router = new Router();
        $controller = new class($router) extends Controller {

            public function initializeRoutes()
            {
            }

            public function index()
            {
                return parent::download(ROOT_DIR . '/lib/to_delete.txt', 'test.txt', true);
            }
        };
        ob_start();
        $controller->index()->send();
        $output = ob_get_clean();
        $headers = xdebug_get_headers();
        self::assertTrue(strpos($output, "Bubu") !== false);
        self::assertTrue(in_array('Content-Disposition:attachment; filename="test.txt"', $headers));
        self::assertFalse(file_exists(ROOT_DIR . '/lib/to_delete.txt'));
    }
}
