<?php namespace Zephyrus\Tests\Network;

use Zephyrus\Application\Controller;
use Zephyrus\Network\Router\Delete;
use Zephyrus\Network\Router\Patch;
use Zephyrus\Network\Router\Post;
use Zephyrus\Network\Router\Put;
use Zephyrus\Network\Router\Root;
use Zephyrus\Network\Router\Get;

#[Root("/toto")]
class AttributeExampleController extends Controller
{
    #[Get("/")]
    public function index()
    {
        return $this->plain('index');
    }

    #[Get("/test")]
    public function test()
    {
        return $this->plain('test');
    }

    #[Post("/login")]
    public function login()
    {
        return $this->plain('this is sparta');
    }

    #[Put("/test")]
    public function update()
    {
        return $this->plain('this is update');
    }

    #[Patch("/test")]
    public function partialUpdate()
    {
        return $this->plain('this is partial update');
    }

    #[Delete("/test")]
    public function remove()
    {
        return $this->plain('this is delete');
    }
}