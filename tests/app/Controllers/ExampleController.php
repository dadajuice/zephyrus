<?php namespace Controllers;

use Zephyrus\Application\Controller;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router\RouteRepository;
use Zephyrus\Network\Router\Get;

/**
 * Example controller with static route definitions through initializeRoutes and also attribute definitions.
 */
class ExampleController extends Controller
{
    public static function initializeRoutes(RouteRepository $repository): void
    {
        $repository->get("/batman", [self::class, "index"]);
        parent::initializeRoutes($repository);
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