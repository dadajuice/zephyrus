<?php namespace Zephyrus\Network\Router;

use Attribute;
use Zephyrus\Network\ContentType;

#[Attribute(Attribute::TARGET_METHOD)]
class Patch extends RouterAttribute
{
    public function __construct(string $route, string|array $acceptedFormats = ContentType::ANY)
    {
        parent::__construct("patch", $route, $acceptedFormats);
    }
}
