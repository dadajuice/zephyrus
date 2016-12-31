<?php namespace Controllers;

use Models\Brokers\ItemBroker;
use Models\Item;
use Zephyrus\Application\Controller;
use Zephyrus\Application\Flash;
use Zephyrus\Application\Form;
use Zephyrus\Application\Routable;
use Zephyrus\Network\Request;
use Zephyrus\Network\Router;
use Zephyrus\Utilities\Validator;

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
        $router->get("/", self::bind("index"));
        $router->get("/insert", self::bind("insertForm"));
        $router->post("/insert", self::bind("insert"));
        $router->get("/test/basic-html", self::bind("displayBasicHtml2"));
    }

    public function index()
    {
        $broker = new ItemBroker();
        $pager = $broker->buildPager($broker->countAll(), 2);
        $items = $broker->findAll();
        $this->render('example', ["items" => $items], $pager);
    }

    public function insert()
    {
        $form = new Form();
        $form->addRule("name", Validator::NOT_EMPTY, "Le nom ne doit pas être vide");
        $form->addRule("price", Validator::NOT_EMPTY, "Le prix ne doit pas être vide");
        $form->addRule("price", Validator::DECIMAL, "Le prix doit être un nombre positif", Form::TRIGGER_FIELD_NO_ERROR);
        $form->addRule("price", function($value) {
            return $value >= 0.01 && $value <= 1000;
        }, "Le prix doit être entre 0.01$ et 1000$", Form::TRIGGER_FIELD_NO_ERROR);

        if (!$form->verify()) {
            $messages = $form->getErrorMessages();
            Flash::error($messages);
            redirect("/insert");
        }

        $item = new Item();
        $item->setName($form->getValue("name"));
        $item->setPrice($form->getValue("price"));
        $broker = new ItemBroker();
        $broker->insert($item);
        Flash::success("Ajout de l'article #" . $item->getId() . " avec succès");
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