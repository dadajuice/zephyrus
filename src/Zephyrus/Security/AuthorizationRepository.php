<?php namespace Zephyrus\Security;

use InvalidArgumentException;
use ReflectionFunctionAbstract;
use Zephyrus\Application\Callback;
use Zephyrus\Network\Request;

class AuthorizationRepository
{
    private static ?AuthorizationRepository $instance = null;
    private array $rules = [];

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function exists(string $name): bool
    {
        return isset($this->rules[$name]);
    }

    public function remove(string $name): void
    {
        unset($this->rules[$name]);
    }

    public function clear(): void
    {
        $this->rules = [];
    }

    public function isAuthorized(string $name, Request $request): bool
    {
        $callback = new Callback($this->rules[$name]);
        $values = $request->getRouteDefinition()->getArgumentValues();
        $arguments = $this->getFunctionArguments($callback->getReflection(), $values);
        return $callback->executeArray(array_merge([$request], $arguments));
    }

    public function addRule(string $name, callable $callback): void
    {
        if (isset($this->rules[$name])) {
            throw new InvalidArgumentException("Authorization rule [$name] is already defined. Rule name must be unique.");
        }
        $this->rules[$name] = $callback;
    }

    public function addSessionRule(string $name, string $key, mixed $value = null): void
    {
        if (isset($this->rules[$name])) {
            throw new InvalidArgumentException("Authorization rule [$name] is already defined. Rule name must be unique.");
        }
        $this->rules[$name] = function (Request $request) use ($key, $value) {
            return isset($_SESSION[$key]) && (is_null($value) || $_SESSION[$key] == $value);
        };
    }

    public function addIpAddressRule(string $name, string $idAddress): void
    {
        if (isset($this->rules[$name])) {
            throw new InvalidArgumentException("Authorization rule [$name] is already defined. Rule name must be unique.");
        }
        $this->rules[$name] = function (Request $request) use ($idAddress) {
            return $request->getClientIp() == $idAddress;
        };
    }

    private function getFunctionArguments(ReflectionFunctionAbstract $reflection, array $values): array
    {
        $arguments = [];
        if (!empty($reflection->getParameters())) {
            foreach ($values as $value) {
                $arguments[] = $value;
            }
        }
        return $arguments;
    }

    private function __construct()
    {

    }
}
