<?php namespace Zephyrus\Exceptions;

class LocalizationException extends \Exception
{
    const ERROR_RESERVED_WORD = 901;
    const ERROR_INVALID_NAMING = 902;

    /**
     * @var string
     */
    private $jsonFile = "";

    /**
     * @var string
     */
    private $additionalInformation = null;

    public function __construct(int $code, string $jsonFile = "", ?string $additionalInformation = null)
    {
        $this->jsonFile = $jsonFile;
        $this->additionalInformation = $additionalInformation;
        $message = $this->codeToMessage($code);
        if (!empty($jsonFile)) {
            $message .= " in localization json file [$jsonFile].";
        }
        parent::__construct($message, $code);
    }

    /**
     * @return string
     */
    public function getJsonFile(): string
    {
        return $this->jsonFile;
    }

    // http://www.php.net/manual/en/function.json-last-error.php
    private function codeToMessage($code)
    {
        switch ($code) {
            case self::ERROR_RESERVED_WORD:
                $message = "Cannot use the detected PHP reserved word [" . $this->additionalInformation . "] as localize key";
                break;
            case self::ERROR_INVALID_NAMING:
                $message = "Cannot use the word [" . $this->additionalInformation . "] as localize key since it doesn't respect the PHP constant definition";
                break;
            case JSON_ERROR_SYNTAX:
                $message = "Syntax error";
                break;
            // @codeCoverageIgnoreStart
            // Hard to test cases of JSON error
            case JSON_ERROR_DEPTH:
                $message = "The maximum stack depth has been exceeded";
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $message = "Invalid or malformed JSON";
                break;
            case JSON_ERROR_CTRL_CHAR:
                $message = "Control character error, possibly incorrectly encoded";
                break;
            case JSON_ERROR_UTF8:
                $message = "Malformed UTF-8 characters, possibly incorrectly encoded";
                break;
            case JSON_ERROR_RECURSION:
                $message = "One or more recursive references in the value to be encoded";
                break;
            case JSON_ERROR_INF_OR_NAN:
                $message = "One or more NAN or INF values in the value to be encoded";
                break;
            case JSON_ERROR_UNSUPPORTED_TYPE:
                $message = "A value of a type that cannot be encoded was given";
                break;
            case JSON_ERROR_INVALID_PROPERTY_NAME:
                $message = "A property name that cannot be encoded was given";
                break;
            case JSON_ERROR_UTF16:
                $message = "Malformed UTF-16 characters, possibly incorrectly encoded";
                break;
            default:
                $message = "Unknown localization error";
                break;
            // @codeCoverageIgnoreEnd
        }
        return $message;
    }
}
