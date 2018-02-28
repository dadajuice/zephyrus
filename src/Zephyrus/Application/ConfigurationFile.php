<?php namespace Zephyrus\Application;

class ConfigurationFile
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $content = [];

    public function __construct(string $filePath)
    {
        $this->path = $filePath;
        if (is_readable($this->path)) {
            $this->content = parse_ini_file($this->path, true, INI_SCANNER_TYPED);
        }
    }

    public function save()
    {
        if (!is_writable(dirname($this->path))) {
            throw new \RuntimeException("Cannot write file [{$this->path}]");
        }
        $file = fopen($this->path, 'w');
        flock($file, LOCK_EX);
        fwrite($file, implode(PHP_EOL, $this->buildConfigurations()) . PHP_EOL);
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

    private function buildConfigurations()
    {
        $data = [];
        foreach ($this->content as $sectionName => $sectionContent) {
            if (is_array($sectionContent)) {
                $data[] = "[$sectionName]";
                $this->buildConfigurationProperty($data, $sectionContent);
            } else {
                $data[] = $sectionName . ' = ' . $this->formatConfigurationData($sectionContent);
            }
            $data[] = null;
        }
        return $data;
    }

    private function formatConfigurationData($data)
    {
        return ((is_bool($data))
            ? var_export($data, true)
            : ((is_numeric($data) || ctype_upper($data)) ? $data : '"' . $data . '"'));
    }

    private function buildConfigurationProperty(array &$data, array $sectionValue)
    {
        foreach ($sectionValue as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $propertyName => $propertyValue) {
                    $data[] = $key . (is_numeric($propertyName))
                        ? $propertyName. '[] = ' . $this->formatConfigurationData($propertyValue)
                        : '[' . $propertyName . '] = ' . $this->formatConfigurationData($propertyValue);
                }
            } else {
                $data[] = $key . ' = ' . $this->formatConfigurationData($value);
            }
        }
    }
}
