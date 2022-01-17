<?php namespace Zephyrus\Security\IntrusionDetection;

class IntrusionMonitor
{
    /**
     * List of all the IDS rules to be verifies.
     *
     * @var array
     */
    private array $rules;

    /**
     * List of parameter names to be excluded from the IDS monitoring. If one of these parameters are encountered, the
     * monitor will skill them entirely. Be cautious about the security implications.
     *
     * @var array
     */
    private array $exceptions = [];

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
     * Specifies the list of parameters to be excluded from the monitoring.
     *
     * @param array $parameters
     */
    public function setExceptions(array $parameters)
    {
        $this->exceptions = $parameters;
    }

    /**
     * Executes the monitoring of the given data. Must be associative array with the key being the parameter name. Will
     * return the resulting impact. The complete details can be obtained with the getReports method.
     *
     * @param array $data
     * @return IntrusionReport
     */
    public function run(array $data): IntrusionReport
    {
        $report = new IntrusionReport();
        foreach ($data as $parameter => $value) {
            if (in_array($parameter, $this->exceptions)) {
                continue;
            }
            foreach ($this->rules as $rule) {
                if ($this->detectIntrusion($rule->rule, $value)) {
                    $report->addIntrusion($rule, $parameter, $value);
                }
            }
        }
        $report->end();
        return $report;
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
        if (is_array($data)) {
            foreach ($data as $value) {
                if ($this->detectIntrusion($regexRule, $value)) {
                    return true;
                }
            }
            return false;
        }
        return preg_match('/'. $regexRule .'/im', $data) === 1;
    }
}
