<?php namespace Zephyrus\Exceptions;

use Exception;

class LocalizationException extends Exception
{
    public const ERROR_RESERVED_WORD = 901;
    public const ERROR_INVALID_NAMING = 902;

    /**
     * The processed JSON localization file which triggered an error.
     *
     * @var string
     */
    private string $jsonFile;

    /**
     * Additional information to pass with the exception if needed.
     *
     * @var string|null
     */
    private ?string $additionalInformation;

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

    /**
     * Builds an error message based on the given code. Most codes follow the JSON_ERROR_x codes from PHP.
     *
     * @see http://www.php.net/manual/en/function.json-last-error.php
     * @param int $code
     * @return string
     */
    private function codeToMessage(int $code): string
    {
        return match ($code) {
            self::ERROR_RESERVED_WORD => "Cannot use the detected PHP reserved word [" . $this->additionalInformation . "] as localize key.",
            self::ERROR_INVALID_NAMING => "Cannot use the word [" . $this->additionalInformation . "] as localize key since it doesn't respect the PHP constant definition.",
            // @codeCoverageIgnoreStart
            // Hard to test cases of JSON error
            JSON_ERROR_SYNTAX => "Syntax error.",
            JSON_ERROR_DEPTH => "The maximum stack depth has been exceeded.",
            JSON_ERROR_STATE_MISMATCH => "Invalid or malformed JSON.",
            JSON_ERROR_CTRL_CHAR => "Control character error, possibly incorrectly encoded.",
            JSON_ERROR_UTF8 => "Malformed UTF-8 characters, possibly incorrectly encoded.",
            JSON_ERROR_RECURSION => "One or more recursive references in the value to be encoded.",
            JSON_ERROR_INF_OR_NAN => "One or more NAN or INF values in the value to be encoded.",
            JSON_ERROR_UNSUPPORTED_TYPE => "A value of a type that cannot be encoded was given.",
            JSON_ERROR_INVALID_PROPERTY_NAME => "A property name that cannot be encoded was given.",
            JSON_ERROR_UTF16 => "Malformed UTF-16 characters, possibly incorrectly encoded.",
            default => "Unknown localization error",
            // @codeCoverageIgnoreEnd
        };
    }
}
