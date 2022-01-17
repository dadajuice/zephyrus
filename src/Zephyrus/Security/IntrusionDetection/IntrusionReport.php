<?php namespace Zephyrus\Security\IntrusionDetection;

use stdClass;

class IntrusionReport
{
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
     * @var float
     */
    private float $begin;

    /**
     * @var float
     */
    private float $totalExecutionTime = 0;

    public function __construct()
    {
        $this->begin = microtime(true);
    }

    /**
     * Adds a new intrusion upon detection by the monitor. Rule object must be compatible with the IntrusionRuleLoader
     * corresponding JSON rule file.
     *
     * @param stdClass $rule
     * @param string $parameter
     * @param mixed $value
     */
    public function addIntrusion(stdClass $rule, string $parameter, mixed $value)
    {
        $this->intrusions[] = (object) [
            'impact' => $rule->impact,
            'description' => $rule->description,
            'tags' => $rule->tags->tag,
            'argument_name' => $parameter,
            'argument_value' => $value
        ];
        $this->impact += $rule->impact;
    }

    /**
     * Retrieves the global calculated impact of the whole report.
     *
     * @return int
     */
    public function getImpact(): int
    {
        return $this->impact;
    }

    /**
     * Verifies if the specified field has triggered an intrusion.
     *
     * @param string $field
     * @return bool
     */
    public function hasDetected(string $field): bool
    {
        foreach ($this->intrusions as $intrusion) {
            if ($intrusion->argument_name == $field) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reads all the detected intrusions details. Returns an array of object containing the following properties:
     * impact, description, tags, argument_name and argument_value. If the field argument is specified, it will return
     * only the intrusion related to that field.
     *
     * @param string|null $field
     * @return stdClass[]
     */
    public function getDetectedIntrusions(?string $field = null): array
    {
        if (is_null($field)) {
            return $this->intrusions;
        }
        $intrusions = [];
        foreach ($this->intrusions as $intrusion) {
            if ($intrusion->argument_name == $field) {
                $intrusions[] = $intrusion;
            }
        }
        return $intrusions;
    }

    /**
     * Retrieves the total execution time (milliseconds) of the intrusion report.
     *
     * @return float
     */
    public function getExecutionTime(): float
    {
        return $this->totalExecutionTime;
    }

    /**
     * Allows to calculate the report execution time correctly.
     */
    public function end()
    {
        $this->totalExecutionTime = microtime(true) - $this->begin;
    }
}
