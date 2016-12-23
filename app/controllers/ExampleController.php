<?php namespace Controllers;

use Models\Brokers\ItemBroker;
use Models\Item;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Routable;
use Zephyrus\Network\Request;
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
        $router->get("/insert", [get_class(), "insertForm"]);
        $router->post("/insert", [get_class(), "insert"]);
        $router->get("/test/basic-html", [get_class(), "displayBasicHtml2"]);
    }

    public function index()
    {
        $broker = new ItemBroker();
        $items = $broker->findAll();
        $m = ["success" => (isset($_SESSION["SUCCESS"])) ? $_SESSION["SUCCESS"] : ""];
        unset($_SESSION["SUCCESS"]);
        $this->render('example', ["items" => $items, "message" => $m]);
    }

    public function insert()
    {
        $item = new Item();
        $item->setName(Request::getParameter("name"));
        $item->setPrice(Request::getParameter("price"));
        $broker = new ItemBroker();
        $broker->insert($item);
        Flash::success("You successfully added item #" . $item->getId());
        redirect("/");
    }

    public function insertForm()
    {
        $this->render('form');
    }

    public function displayBasicHtml()
    {
        ob_start();
        ?>
        <p>Testing simple HTML integration without <b>parsing</b> or template</p>
        <?php
        $this->html(ob_get_clean());
    }

    public function displayBasicHtml2()
    {
        ?>
        <p>Testing without ob_start()</p>
        <?php
    }
}