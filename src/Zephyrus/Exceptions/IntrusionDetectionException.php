<?php namespace Zephyrus\Exceptions;

use Zephyrus\Security\IntrusionDetection\IntrusionReport;

class IntrusionDetectionException extends \Exception
{
    private IntrusionReport $report;

    public function __construct(IntrusionReport $report)
    {
        parent::__construct("IDS: Possible intrusion detected of impact " . $report->getImpact() . ".");
        $this->report = $report;
    }

    /**
     * @return int
     */
    public function getImpact(): int
    {
        return $this->report->getImpact();
    }

    /**
     * @param string|null $field
     * @return array
     */
    public function getDetectedIntrusions(?string $field = null): array
    {
        return $this->report->getDetectedIntrusions($field);
    }

    /**
     * @return IntrusionReport
     */
    public function getReport(): IntrusionReport
    {
        return $this->report;
    }
}
