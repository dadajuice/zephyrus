<?php namespace Zephyrus\Exceptions\Security;

use Zephyrus\Security\IntrusionDetection\IntrusionReport;

class IntrusionDetectionException extends SecurityException
{
    private IntrusionReport $report;

    public function __construct(IntrusionReport $report)
    {
        parent::__construct("Possible intrusion detected of impact " . $report->getImpact() . ".", 14001);
        $this->report = $report;
    }

    public function getImpact(): int
    {
        return $this->report->getImpact();
    }

    public function getDetectedIntrusions(?string $field = null): array
    {
        return $this->report->getDetectedIntrusions($field);
    }

    public function getReport(): IntrusionReport
    {
        return $this->report;
    }
}
