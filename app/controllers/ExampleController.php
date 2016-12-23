<?php namespace Controllers;

use Zephyrus\Application\Controller;
use Zephyrus\Application\Routable;
use Zephyrus\Network\Router;

class ExampleController extends Controller implements Routable
{
    /**
     * Defines all the routes supported by this controller associated with
     * inner methods.
     *
     * @param Router $router
     */
    public static function initializeRoutes(Router $router)
    {
        $router->get("/", [get_class(), "index"]);
    }

    public function index()
    {
        $items = [
            ['href' => 'bob', 'name' => 'Un beau petit test'],
            ['href' => 'lewis', 'name' => 'Un beau petit test 2'],
        ];
        $this->render('example', ["items" => $items]);
    }
}