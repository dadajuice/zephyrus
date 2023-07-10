<?php namespace Zephyrus\Network;

class QueryString
{
    private string $rawQueryString;
    private array $rawArguments;
    private array $arguments;

    public function __construct(string $rawQueryString)
    {
        $this->rawQueryString = $rawQueryString;
        $this->arguments = $this->rawArguments = $this->buildArguments();
    }

    /**
     * Adds or updates a query string argument using the given name and value.
     *
     * @param string $name
     * @param mixed $value
     * @return QueryString
     */
    public function setArgument(string $name, mixed $value): self
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    public function removeArgumentEquals(string $name): self
    {
        $this->arguments = array_filter($this->arguments, function ($key) use ($name) {
            return $key != $name;
        }, ARRAY_FILTER_USE_KEY);
        return $this;
    }

    public function removeArgumentStartsWith(string $name): self
    {
        $this->arguments = array_filter($this->arguments, function ($key) use ($name) {
            return !str_starts_with($key, $name);
        }, ARRAY_FILTER_USE_KEY);
        return $this;
    }

    public function removeArgumentEndsWith(string $name): self
    {
        $this->arguments = array_filter($this->arguments, function ($key) use ($name) {
            return !str_ends_with($key, $name);
        }, ARRAY_FILTER_USE_KEY);
        return $this;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getRawArguments(): array
    {
        return $this->rawArguments;
    }

    public function buildString(): string
    {
        return http_build_query($this->arguments);
    }

    public function __toString(): string
    {
        return $this->buildString();
    }

    private function buildArguments(): array
    {
        $arguments = [];
        if (!empty($this->rawQueryString)) {
            foreach (explode("&", $this->rawQueryString) as $rawArguments) {
                $argument = explode("=", $rawArguments);
                $argument[0] = urldecode($argument[0]);
                $argument[1] = urldecode($argument[1]);
                $arguments[$argument[0]] = $argument[1];
            }
        }
        return $arguments;
    }
}
