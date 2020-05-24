<?php namespace Controllers;

use Zephyrus\Security\Controller;

class ExampleController extends Controller
{
    public function initializeRoutes()
    {
        $this->get("/batman", "index");
    }

    public function index()
    {
        return $this->plain("batman rocks!");
    }
}