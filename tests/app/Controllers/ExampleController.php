<?php namespace Controllers;

use Zephyrus\Network\Response;
use Zephyrus\Security\Controller;
use Zephyrus\Network\Router\Get;

/**
 * Example controller with static route definitions through initializeRoutes and also attribute definitions.
 */
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

    #[Get("/robin")]
    public function robin(): Response
    {
        return $this->plain("robin test");
    }

    public function setupSecurity(): void
    {

    }
}