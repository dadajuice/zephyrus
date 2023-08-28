<?php namespace Zephyrus\Tests\Application\Controllers;

use PHPUnit\Framework\TestCase;
use Zephyrus\Application\Controller;
use Zephyrus\Network\Router;
use Zephyrus\Utilities\FileSystem\File;

class ControllerDownloadTest extends TestCase
{
    public function testDownload()
    {
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        self::assertTrue(str_contains($output, "[application]"));
        self::assertTrue(in_array('Content-Disposition:attachment; filename="config.ini"', $headers));
    }

    public function testDownloadAndDelete()
    {
        $f = File::create(ROOT_DIR . '/lib/to_delete.txt');
        $f->write("Bubu");
        self::assertTrue(file_exists(ROOT_DIR . '/lib/to_delete.txt'));
        $controller = new class() extends Controller {

            public function initializeRoutes(): void
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
        self::assertTrue(str_contains($output, "Bubu"));
        self::assertTrue(in_array('Content-Disposition:attachment; filename="test.txt"', $headers));
        self::assertFalse(file_exists(ROOT_DIR . '/lib/to_delete.txt'));
    }
}
