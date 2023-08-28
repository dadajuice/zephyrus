<?php namespace Zephyrus\Network\Router;

use Attribute;
use Zephyrus\Network\ContentType;

#[Attribute(Attribute::TARGET_METHOD)]
class Post extends RouterAttribute
{
    public function __construct(string $route, string|array $acceptedFormats = ContentType::ANY)
    {
        parent::__construct("post", $route, $acceptedFormats);
    }
}
