<?php namespace Zephyrus\Security\IntrusionDetection;

use InvalidArgumentException;
use RuntimeException;
use Zephyrus\Utilities\FileSystem\File;

class IntrusionRuleLoader
{
    private const DEFAULT_FILE = 'default_filter_rules.json';

    /**
     * @var string
     */
    private string $filePath;

    public function __construct(?string $ruleFile = null)
    {
        if (is_null($ruleFile)) {
            $ruleFile = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::DEFAULT_FILE;
        }
        if (!File::exists($ruleFile)) {
            throw new InvalidArgumentException("Given IDS rule file is not accessible.");
        }
        $this->filePath = $ruleFile;
    }

    public function loadFromFile(): array
    {
        $json = json_decode(file_get_contents($this->filePath));
        if ($json === false || !is_object($json) || !property_exists($json, "filters")) {
            throw new RuntimeException("Unable to parse IDS rule JSON file.");
        }
        return $json->filters;
    }
}
