<?php namespace Zephyrus\Network\Router;

use Attribute;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpMethod;

#[Attribute(Attribute::TARGET_METHOD)]
class Put extends RouterAttribute
{
    public function __construct(string $route, array $acceptedFormats = [ContentType::ANY])
    {
        parent::__construct(HttpMethod::PUT, $route, $acceptedFormats);
    }
}
