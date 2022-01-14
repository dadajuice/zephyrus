<?php namespace Zephyrus\Security\IntrusionDetection;

use stdClass;

class IntrusionMonitor
{
    /**
     * List of all the IDS rules to be verifies.
     *
     * @var array
     */
    private array $rules;

    /**
     * List of all the intrusion detailed data which has been detected by the run method.
     *
     * @var array
     */
    private array $intrusions = [];

    /**
     * Global impact of all the intrusion detected.
     *
     * @var int
     */
    private int $impact = 0;

    /**
     * Prepares the monitor with a set of IDS rules to verify.
     *
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Executes the monitoring of the given data. Must be associative array with the key being the parameter name. Will
     * return the resulting impact. The complete details can be obtained with the getReports method.
     *
     * @param array $data
     * @return int
     */
    public function run(array $data): int
    {
        foreach ($data as $parameter => $value) {
            $this->executeAllRules($parameter, $value);
        }
        return $this->impact;
    }

    public function getImpact(): int
    {
        return $this->impact;
    }

    public function getReports(): array
    {
        return $this->intrusions;
    }

    private function executeAllRules($parameter, $value)
    {
        foreach ($this->rules as $intrusionRule) {
            if ($this->detectIntrusion($intrusionRule->rule, $value)) {
                $this->addIntrusion($intrusionRule, $parameter, $value);
                $this->impact += $intrusionRule->impact;
            }
        }
    }

    private function addIntrusion(stdClass $rule, string $parameter, $value)
    {
        $this->intrusions[] = (object) [
            'impact' => $rule->impact,
            'description' => $rule->description,
            'tags' => $rule->tags->tag,
            'argument_name' => $parameter,
            'argument_value' => $value
        ];
    }

    /**
     * Executes the regex matching against the request data. If it matches, it means an intrusion has been detected.
     *
     * @param string $regexRule
     * @param $data
     * @return bool
     */
    private function detectIntrusion(string $regexRule, $data): bool
    {
        return preg_match('/'. $regexRule .'/im', $data) === 1;
    }
}
