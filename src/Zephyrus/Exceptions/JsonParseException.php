<?php namespace Zephyrus\Exceptions;

class JsonParseException extends ZephyrusException
{
    private string $rawJson;

    public function __construct(string $rawJson)
    {
        $code = json_last_error();
        $this->rawJson = $rawJson;
        $message = $this->codeToMessage($code);
        parent::__construct("JSON parsing failed with message [$message]. Consult the raw data for more information.", $code);
    }

    public function getRawJson(): string
    {
        return $this->rawJson;
    }

    private function codeToMessage(int $code): string
    {
        return match ($code) {
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
            default => "Unknown Json error",
            // @codeCoverageIgnoreEnd
        };
    }
}
