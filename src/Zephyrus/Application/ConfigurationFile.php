<?php namespace Zephyrus\Application;

use RuntimeException;

class ConfigurationFile
{
    private string $path;
    private array $content = [];

    public function __construct(string $filePath)
    {
        $this->path = $filePath;
        if (is_readable($this->path)) {
            $this->content = parse_ini_file($this->path, true, INI_SCANNER_TYPED);
        }
    }

    public function save(): bool
    {
        if (!is_writable(dirname($this->path))) {
            throw new RuntimeException("Cannot write file [$this->path]");
        }
        $file = fopen($this->path, 'w');
        flock($file, LOCK_EX);
        fwrite($file, implode(PHP_EOL, $this->buildConfigurations()));
        flock($file, LOCK_UN);
        fclose($file);
        return true;
    }

    public function read(?string $section = null, ?string $property = null, $defaultValue = null)
    {
        if (is_null($section)) {
            return $this->content;
        }
        return (is_null($property))
            ? $this->content[$section] ?? $defaultValue
            : $this->content[$section][$property] ?? $defaultValue;
    }

    public function write(array $content)
    {
        $this->content = $content;
    }

    public function writeSection(string $section, $properties)
    {
        $this->content[$section] = $properties;
    }

    private function buildConfigurations(): array
    {
        $data = [];
        foreach ($this->content as $sectionName => $sectionContent) {
            if (is_array($sectionContent)) {
                $data[] = "[$sectionName]";
                $this->buildConfigurationProperty($data, $sectionContent);
            } elseif (is_bool($sectionContent)) {
                $data[] = $sectionName . ' = ' . var_export($sectionContent, true);
            } else {
                $data[] = $sectionName . ' = ' . $this->formatConfigurationData($sectionContent);
            }
            $data[] = null;
        }
        return $data;
    }

    private function formatConfigurationData(string $data): string
    {
        return ((defined($data) || is_numeric($data)) ? $data : '"' . $data . '"');
    }

    private function buildConfigurationProperty(array &$data, array $sectionValue)
    {
        foreach ($sectionValue as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $propertyName => $propertyValue) {
                    $data[] = $key . ((is_numeric($propertyName))
                        ? '[] = ' . $this->formatConfigurationData($propertyValue)
                        : '[' . $propertyName . '] = ' . $this->formatConfigurationData($propertyValue));
                }
            } else {
                $data[] = $key . ' = ' . $this->formatConfigurationData($value);
            }
        }
    }
}
