<?php namespace Controllers;

use Zephyrus\Network\Response;
use Zephyrus\Security\Controller;

class ExampleController extends Controller
{
    public function initializeRoutes(): void
    {
        $this->get("/batman", "index");
    }

    public function index(): Response
    {
        return $this->plain("batman rocks!");
    }

    public function setupSecurity(): void
    {

    }
}