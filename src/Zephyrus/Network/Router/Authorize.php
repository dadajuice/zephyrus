<?php namespace Zephyrus\Network\Router;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Authorize
{
    private array $rules;
    private bool $strict;

    /**
     * The annotation will be used when resolving a route. By default, one of the given rules must pass to allow access
     * to the route. If the strict parameter is true, all of the rules must pass to allow access.
     *
     * @param array|string $rules
     * @param bool $strict
     */
    public function __construct(array|string $rules, bool $strict = false)
    {
        $this->rules = is_array($rules) ? $rules : [$rules];
        $this->strict = $strict;
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }
}
