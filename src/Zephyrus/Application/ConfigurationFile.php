<?php namespace Zephyrus\Application;

use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigurationFile
{
    private string $path;
    private array $content = [];

    public function __construct(string $filePath)
    {
        $this->path = $filePath;
        if (is_readable($this->path)) {
            try {
                $this->content = Yaml::parseFile($this->path, Yaml::PARSE_CONSTANT);
            } catch (ParseException $exception) {
                throw new RuntimeException("Unable to parse the YAML string [{$exception->getMessage()}]");
            }
        }
    }

    public function save(): void
    {
        if (!is_writable(dirname($this->path))) {
            throw new RuntimeException("Cannot write file [$this->path]");
        }
        $yaml = Yaml::dump($this->content);
        file_put_contents($this->path, $yaml);
    }

    public function read(?string $property = null, $defaultValue = null): mixed
    {
        return (is_null($property))
            ? $this->content
            : $this->content[$property] ?? $defaultValue;
    }

    public function write(array $content): void
    {
        $this->content = $content;
    }

    public function writeProperty(string $property, mixed $value): void
    {
        $this->content[$property] = $value;
    }
}
