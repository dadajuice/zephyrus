<?php namespace Zephyrus\Network\Extensions;

use Zephyrus\Network\Request;

class ListConfiguration
{
    public const DEFAULT_LIST_IDENTIFIER = "_default";

    private Request $request;
    private array $configurations = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getConfigurations(): array
    {
        return $this->configurations;
    }

    public function getConfiguration(string $listIdentifier = self::DEFAULT_LIST_IDENTIFIER): array
    {
        return $this->configurations[$listIdentifier] ?? [];
    }

    public function parse(): void
    {
        $pattern = '/^(.+_)?(filters|sorts|limit|page|search)$/';
        $parameters = [];
        foreach ($this->request->getParameters() as $parameter => $value) {
            if (preg_match($pattern, $parameter, $matches)) {
                $key = empty($matches[1]) ? self::DEFAULT_LIST_IDENTIFIER : rtrim($matches[1], "_");
                $filterType = $matches[2];
                if (!isset($parameters[$key])) {
                    $parameters[$key] = [];
                }
                $parameters[$key][$filterType] = $value;
            }
        }
        $this->configurations = $parameters;
    }
}
