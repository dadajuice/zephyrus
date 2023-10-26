<?php namespace Zephyrus\Network\Router;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Root
{
    private string $baseRoute;

    public function __construct(string $baseRoute)
    {
        $this->baseRoute = $baseRoute;
    }

    public function getBaseRoute(): string
    {
        return $this->baseRoute;
    }
}
