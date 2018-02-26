<?php namespace Zephyrus\Exceptions;

class IntrusionDetectionException extends \Exception
{
    /**
     * @var array
     */
    private $intrusionData;

    public function __construct(array $intrusionData)
    {
        $this->intrusionData = $intrusionData;
    }

    /**
     * @return array
     */
    public function getIntrusionData(): array
    {
        return $this->intrusionData;
    }
}
