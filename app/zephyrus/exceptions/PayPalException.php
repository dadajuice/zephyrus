<?php namespace Zephyrus\Exceptions;

class PayPalException extends \Exception
{
    /**
     * @var array
     */
    private $originResponseHash;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * @var string
     */
    private $correlationId;

    /**
     * @var string
     */
    private $acknowledgement;

    /**
     * @var string
     */
    private $version;

    /**
     * @var string
     */
    private $build;

    /**
     * @var array
     */
    private $errors = [];

    public function __construct($responseHash)
    {
        $this->originResponseHash = $responseHash;
        $this->correlationId = $responseHash['CORRELATIONID'];
        $this->timestamp = $responseHash['TIMESTAMP'];
        $this->acknowledgement = $responseHash['ACK'];
        $this->version = $responseHash['VERSION'];
        $this->build = $responseHash['BUILD'];

        foreach ($this->originResponseHash as $key => $value) {
            if (strpos($key, "L_ERRORCODE") !== false) {
                $number = substr($key, 11);
                $this->errors[] = [
                    "code" => $value,
                    "shortMessage" => $responseHash['L_SHORTMESSAGE' . $number],
                    "longMessage" => $responseHash['L_LONGMESSAGE' . $number],
                    "severity" => $responseHash['L_SEVERITYCODE' . $number]
                ];
            }
        }

        $this->message = "PayPal error occurred";
    }

    /**
     * @return bool
     */
    public function isFundingFailure()
    {
        $result = false;
        foreach ($this->errors as $error) {
            if ($error['code'] == "10486") {
                $result = true;
                break;
            }
        }
        return $result && count($this->errors) == 1;
    }

    /**
     * @return array
     */
    public function getOriginResponseHash()
    {
        return $this->originResponseHash;
    }

    /**
     * @return string
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getCorrelationId()
    {
        return $this->correlationId;
    }

    /**
     * @return string
     */
    public function getAcknowledgement()
    {
        return $this->acknowledgement;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
}