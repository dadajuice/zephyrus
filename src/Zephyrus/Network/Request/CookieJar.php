<?php namespace Zephyrus\Network\Request;

use Zephyrus\Network\Cookie;

class CookieJar
{
    private array $cookies;

    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function create(string $name, string $value): Cookie
    {
        return new Cookie($name, $value);
    }

    public function destroy(string $name): void
    {
        if (isset($this->cookies[$name])) {
            (new Cookie($name))->destroy();
        }
    }

    public function get(string $name, ?string $defaultValue = null): ?string
    {
        return $this->cookies[$name] ?? $defaultValue;
    }

    public function getAll(): array
    {
        return $this->cookies;
    }

    public function exists(string $name): bool
    {
        return isset($this->cookies[$name]);
    }
}
